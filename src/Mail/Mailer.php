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

    /** Deposit / withdraw notification. $event: deposit_success | withdraw_success | withdraw_failed */
    public static function sendTxNotice(string $to, string $event, string $amountFmt, string $lang): bool
    {
        return self::send($to, Lang::t("mail.tx.$event.subject", [], $lang), 'security', [
            'title' => Lang::t("mail.tx.$event.title", [], $lang),
            'intro' => Lang::t("mail.tx.$event.intro", ['amount' => $amountFmt], $lang),
        ], $lang);
    }

    private static function send(string $to, string $subject, string $template, array $vars, string $lang): bool
    {
        $c = Config::get('smtp');
        if (empty($c['host']) || empty($c['from_email'])) {
            Logger::error('mail.not_configured');
            return false;
        }
        $vars += ['lang' => $lang, 'appName' => (string)Config::get('app.name')];
        $vars['footer'] = Lang::t('mail.footer', [], $lang);
        $vars['notice'] = Lang::t('mail.notice', [], $lang);

        extract($vars, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__, 2) . "/templates/emails/{$template}.php";
        $html = (string)ob_get_clean();

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
            $m->AltBody = trim(strip_tags(str_replace(['</p>', '<br>'], "\n", $html)));
            $m->send();
            return true;
        } catch (MailException $e) {
            Logger::error('mail.send_failed', ['template' => $template, 'e' => $e->getMessage()]);
            return false;
        }
    }
}
