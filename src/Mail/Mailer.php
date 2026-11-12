<?php
declare(strict_types=1);

namespace App\Mail;

use App\Core\Config;
use App\Core\Lang;
use App\Core\Logger;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

final class Mailer
{
    public static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Branded OTP email. $purpose = register|login|reset|email_change|password_change|withdraw */
    public static function sendOtp(string $to, string $purpose, string $code, int $expiresInSec, string $lang): bool
    {
        $minutes = (int)ceil($expiresInSec / 60);
        return self::send($to, Lang::t("mail.otp.$purpose.subject", [], $lang), 'otp', [
            'title'   => Lang::t("mail.otp.$purpose.title", [], $lang),
            'intro'   => Lang::t("mail.otp.$purpose.intro", [], $lang),
            'code'    => $code,
            'expires' => Lang::t('mail.expires', ['min' => $minutes], $lang),
        ], $lang);
    }

    public static function sendSecurityNotice(string $to, string $eventKey, string $lang): bool
    {
        return self::send($to, Lang::t('mail.security.subject', [], $lang), 'security', [
            'title' => Lang::t('mail.security.title', [], $lang),
            'intro' => Lang::t($eventKey, [], $lang),
        ], $lang);
    }

    private static function send(string $to, string $subject, string $template, array $vars, string $lang): bool
    {
        $vars += ['lang' => $lang, 'appName' => (string)Config::get('app.name')];
        $vars['footer'] = Lang::t('mail.footer', [], $lang);
        $vars['notice'] = Lang::t('mail.notice', [], $lang);

        extract($vars, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__, 2) . "/templates/emails/{$template}.php";
        $html = (string)ob_get_clean();
        $plain = trim(strip_tags(str_replace(['</p>', '<br>'], "\n", $html)));

        // Try SMTP first if configured
        $c = Config::get('smtp');
        if (!empty($c['host']) && !empty($c['from_email'])) {
            try {
                $m = new PHPMailer(true);
                $m->isSMTP();
                $m->Host = $c['host'];
                $m->Port = $c['port'];
                $m->SMTPAuth = $c['user'] !== null;
                $m->Username = (string)$c['user'];
                $m->Password = (string)$c['pass'];
                $m->SMTPSecure = $c['port'] === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $m->Timeout = 10;
                $m->CharSet = 'UTF-8';
                $m->setFrom($c['from_email'], $c['from_name']);
                $m->addAddress($to);
                $m->isHTML(true);
                $m->Subject = $subject;
                $m->Body = $html;
                $m->AltBody = $plain;
                $m->send();
                return true;
            } catch (MailException $e) {
                Logger::error('mail.smtp_failed_trying_firebase', ['e' => $e->getMessage()]);
                // Fall through to Firebase
            }
        }

        // Fallback: Firebase Email API (HTTPS — not blocked by Render)
        return self::sendViaFirebase($to, $subject, $html, $plain);
    }

    /**
     * Send email via Brevo Transactional Email API (HTTPS — no SMTP port needed).
     * Requires BREVO_API_KEY environment variable.
     */
    private static function sendViaFirebase(string $to, string $subject, string $html, string $plain): bool
    {
        $apiKey = (string)Config::get('brevo.api_key', '');
        if ($apiKey === '') {
            Logger::error('mail.brevo_api_key_missing');
            return false;
        }

        $fromEmail = (string)Config::get('brevo.from_email', '');
        $fromName  = (string)Config::get('brevo.from_name', Config::get('app.name'));
        if ($fromEmail === '') {
            Logger::error('mail.brevo_from_missing');
            return false;
        }

        $payload = json_encode([
            'sender'      => ['name' => $fromName, 'email' => $fromEmail],
            'to'          => [['email' => $to]],
            'subject'     => $subject,
            'htmlContent' => $html,
            'textContent' => $plain,
        ]);

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'api-key: ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $raw  = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http >= 200 && $http < 300) {
            return true;
        }

        $err = is_string($raw) ? (json_decode($raw, true)['message'] ?? $raw) : 'curl_failed';
        Logger::error('mail.brevo_send_failed', ['to' => $to, 'http' => $http, 'err' => $err]);
        return false;
    }
}
