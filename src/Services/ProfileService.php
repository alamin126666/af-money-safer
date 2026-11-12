<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Core\Config;
use App\Core\Firebase;
use App\Core\Lang;
use App\Core\RateLimit;
use App\Core\Request;
use App\Core\Validator;
use App\Mail\Mailer;
use Google\Cloud\Core\Blob;
use Kreait\Firebase\Exception\Auth\UserNotFound;

final class ProfileService
{
    public const PRESETS = ['default', 'p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'];

    private static function s(array $in, string $k, int $max = 256): string
    {
        $v = $in[$k] ?? '';
        return is_string($v) ? mb_substr($v, 0, $max) : '';
    }
    private static function ref(string $uid) { return Firebase::db()->collection('users')->document($uid); }

    public static function info(array $user): array
    {
        $d = self::ref($user['uid'])->snapshot()->data();
        $created = $d['createdAt'] ?? null;
        $ms = is_object($created) && method_exists($created, 'get') ? $created->get()->getTimestamp() * 1000 : 0;
        $avatar = $d['avatar'] ?? ['type' => 'preset', 'key' => 'default'];
        return ['uid' => $user['uid'], 'fullName' => $d['fullName'], 'email' => $d['email'],
                'accountId' => strtoupper(substr(hash('sha256', $user['uid']), 0, 10)),
                'registeredAtMs' => $ms, 'avatar' => $avatar, 'isAdmin' => AdminGuard::isAdminSession(), 'lang' => $d['lang'] ?? 'bn', 'theme' => $d['theme'] ?? 'light',
                'totpEnabled' => (bool)($d['totpEnabled'] ?? false)];
    }

    public static function updateName(array $user, array $in): array
    {
        RateLimit::hit("name:{$user['uid']}", 10, 3600);
        $name = Validator::name(self::s($in, 'fullName', 120));
        Validator::failIf($name === null ? ['fullName' => 'FIELD_NAME_INVALID'] : []);
        Firebase::auth()->updateUser($user['uid'], ['displayName' => $name]);
        self::ref($user['uid'])->update([['path' => 'fullName', 'value' => $name]]);
        unset($_SESSION['userCache']);
        AuditLog::log($user['uid'], 'profile.name_changed', "users/{$user['uid']}");
        return ['NAME_UPDATED', ['fullName' => $name]];
    }

    public static function setPreferences(array $user, array $in): array
    {
        $f = [];
        if (in_array($in['lang'] ?? null, Config::get('app.languages'), true)) { $f[] = ['path' => 'lang', 'value' => $in['lang']]; }
        if (in_array($in['theme'] ?? null, Config::get('app.themes'), true)) { $f[] = ['path' => 'theme', 'value' => $in['theme']]; }
        if ($f) { self::ref($user['uid'])->update($f); }
        return ['OK', []];
    }

    // ------------------------------------------------------------- e-mail change
    public static function emailChange(array $user, array $in): array
    {
        return ($in['step'] ?? '') === 'confirm' ? self::emailConfirm($user, $in) : self::emailRequest($user, $in);
    }

    private static function emailRequest(array $user, array $in): array
    {
        RateLimit::hit("email_change:{$user['uid']}", 5, 3600);
        $new = Validator::email(self::s($in, 'newEmail', 254));
        Validator::failIf($new === null ? ['newEmail' => 'FIELD_EMAIL_INVALID'] : ($new === $user['email'] ? ['newEmail' => 'FIELD_EMAIL_SAME'] : []));
        AuthService::verifyPassword($user['uid'], $user['email'], self::s($in, 'password', 129));
        try {
            Firebase::auth()->getUserByEmail($new);
            throw new ApiException('EMAIL_EXISTS', 409);
        } catch (UserNotFound) {}

        $otp = OtpService::issue('email_change', $new, $user['uid'], $_SESSION['change']['otpId'] ?? null);
        $_SESSION['change'] = ['kind' => 'email', 'otpId' => $otp['id'], 'newEmail' => $new, 'uid' => $user['uid'], 'sentAt' => time()];
        if (!Mailer::sendOtp($new, 'email_change', $otp['code'], $otp['expiresIn'], Lang::current())) { throw new ApiException('MAIL_FAILED', 502); }
        return ['EMAIL_CHANGE_CODE_SENT', ['email' => AuthService::maskEmail($new), 'expiresIn' => $otp['expiresIn'], 'resendIn' => (int)Config::get('otp.resend_after')]];
    }

    private static function emailConfirm(array $user, array $in): array
    {
        $c = $_SESSION['change'] ?? null;
        if (!is_array($c) || $c['kind'] !== 'email' || $c['uid'] !== $user['uid'] || time() - (int)$c['sentAt'] > 1800) { throw new ApiException('NO_PENDING', 400); }
        RateLimit::hit('otp_verify_ip:' . Request::ip(), 30, 900);
        $r = OtpService::verify((string)$c['otpId'], 'email_change', preg_replace('/\D/', '', self::s($in, 'code', 12)) ?? '');
        if ($r['status'] !== 'ok') { throw new ApiException($r['status'], 400, ['attemptsLeft' => $r['attemptsLeft']]); }

        $new = (string)$c['newEmail']; $old = $user['email'];
        try { Firebase::auth()->getUserByEmail($new); throw new ApiException('EMAIL_EXISTS', 409); } catch (UserNotFound) {}
        Firebase::auth()->updateUser($user['uid'], ['email' => $new, 'emailVerified' => true]);
        Firebase::auth()->revokeRefreshTokens($user['uid']);
        self::ref($user['uid'])->update([['path' => 'email', 'value' => $new], ['path' => 'emailLower', 'value' => $new], ['path' => 'emailVerified', 'value' => true]]);
        unset($_SESSION['change'], $_SESSION['userCache']);
        AuthService::revokeAllSessions($user['uid']);       // other devices are signed out; this session is re-written at the end of the request
        AuditLog::log($user['uid'], 'profile.email_changed', "users/{$user['uid']}");
        Mailer::sendSecurityNotice($old, 'mail.security.email_changed', Lang::current());
        return ['EMAIL_CHANGED', ['email' => $new]];
    }

    // ----------------------------------------------------------- password change
    public static function passwordChange(array $user, array $in): array
    {
        return ($in['step'] ?? '') === 'confirm' ? self::pwConfirm($user, $in) : self::pwRequest($user, $in);
    }

    private static function pwRequest(array $user, array $in): array
    {
        RateLimit::hit("pw_change:{$user['uid']}", 5, 3600);
        AuthService::verifyPassword($user['uid'], $user['email'], self::s($in, 'currentPassword', 129));
        $otp = OtpService::issue('password_change', $user['email'], $user['uid'], $_SESSION['change']['otpId'] ?? null);
        $_SESSION['change'] = ['kind' => 'password', 'otpId' => $otp['id'], 'uid' => $user['uid'], 'sentAt' => time()];
        if (!Mailer::sendOtp($user['email'], 'password_change', $otp['code'], $otp['expiresIn'], Lang::current())) { throw new ApiException('MAIL_FAILED', 502); }
        return ['PASSWORD_CODE_SENT', ['email' => AuthService::maskEmail($user['email']), 'expiresIn' => $otp['expiresIn'], 'resendIn' => (int)Config::get('otp.resend_after')]];
    }

    private static function pwConfirm(array $user, array $in): array
    {
        $c = $_SESSION['change'] ?? null;
        if (!is_array($c) || $c['kind'] !== 'password' || $c['uid'] !== $user['uid'] || time() - (int)$c['sentAt'] > 1800) { throw new ApiException('NO_PENDING', 400); }
        $pw = self::s($in, 'newPassword', 129); $pw2 = self::s($in, 'confirmPassword', 129);
        $err = [];
        if (!Validator::passwordStrong($pw, $user['email'])) { $err['newPassword'] = 'FIELD_PASSWORD_WEAK'; }
        elseif ($pw !== $pw2) { $err['confirmPassword'] = 'FIELD_PASSWORD_MISMATCH'; }
        Validator::failIf($err);
        RateLimit::hit('otp_verify_ip:' . Request::ip(), 30, 900);
        $r = OtpService::verify((string)$c['otpId'], 'password_change', preg_replace('/\D/', '', self::s($in, 'code', 12)) ?? '');
        if ($r['status'] !== 'ok') { throw new ApiException($r['status'], 400, ['attemptsLeft' => $r['attemptsLeft']]); }

        Firebase::auth()->changeUserPassword($user['uid'], $pw);
        Firebase::auth()->revokeRefreshTokens($user['uid']);
        unset($_SESSION['change']);
        AuthService::revokeAllSessions($user['uid']);
        AuditLog::log($user['uid'], 'profile.password_changed', "users/{$user['uid']}");
        Mailer::sendSecurityNotice($user['email'], 'mail.security.password_changed', Lang::current());
        return ['PASSWORD_CHANGED', []];
    }

    // ------------------------------------------------------------------- photo
    public static function uploadPhoto(array $user): array
    {
        RateLimit::hit("photo:{$user['uid']}", 10, 3600);
        $f = $_FILES['photo'] ?? null;
        if (!is_array($f) || ($f['error'] ?? 1) !== UPLOAD_ERR_OK || !is_uploaded_file((string)$f['tmp_name'])) { throw new ApiException('INVALID_IMAGE', 422); }
        if ((int)$f['size'] > 7 * 1024 * 1024) { throw new ApiException('FILE_TOO_LARGE', 413); }
        $raw = (string)file_get_contents((string)$f['tmp_name']);
        $mime = (string)(new \finfo(FILEINFO_MIME_TYPE))->buffer($raw);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) { throw new ApiException('INVALID_IMAGE', 422); }
        $info = @getimagesizefromstring($raw);
        if ($info === false || $info[0] < 32 || $info[1] < 32 || $info[0] > 6000 || $info[1] > 6000) { throw new ApiException('INVALID_IMAGE', 422); }
        $src = @imagecreatefromstring($raw);
        if ($src === false) { throw new ApiException('INVALID_IMAGE', 422); }

        // Re-encode: centre-crop to a 256x256 JPEG. Drops EXIF and anything hidden in the original file.
        $side = min($info[0], $info[1]);
        $dst = imagecreatetruecolor(256, 256);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, intdiv($info[0] - $side, 2), intdiv($info[1] - $side, 2), 256, 256, $side, $side);
        ob_start(); imagejpeg($dst, null, 82); $jpg = (string)ob_get_clean();
        imagedestroy($src); imagedestroy($dst);

        $key = bin2hex(random_bytes(16));                      // random name, never derived from the upload
        Firebase::db()->collection('avatars')->document($user['uid'])->set(['data' => new Blob($jpg), 'mime' => 'image/jpeg', 'key' => $key]);
        self::ref($user['uid'])->update([['path' => 'avatar', 'value' => ['type' => 'upload', 'key' => $key]]]);
        AuditLog::log($user['uid'], 'profile.photo_changed', "users/{$user['uid']}");
        return ['PHOTO_UPDATED', ['avatar' => ['type' => 'upload', 'key' => $key]]];
    }

    public static function setAvatar(array $user, array $in): array
    {
        $k = self::s($in, 'key', 20);
        if (!in_array($k, self::PRESETS, true)) { throw new ApiException('BAD_REQUEST', 400); }
        self::ref($user['uid'])->update([['path' => 'avatar', 'value' => ['type' => 'preset', 'key' => $k]]]);
        return ['AVATAR_UPDATED', ['avatar' => ['type' => 'preset', 'key' => $k]]];
    }

    public static function streamAvatar(array $user): never
    {
        $s = Firebase::db()->collection('avatars')->document($user['uid'])->snapshot();
        if (!$s->exists()) { http_response_code(404); exit; }
        $bytes = (string)$s->data()['data'];
        header('Content-Type: image/jpeg');
        header('Cache-Control: private, max-age=86400');
        header('Content-Length: ' . strlen($bytes));
        echo $bytes;
        exit;
    }
}
