<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Firebase;
use App\Core\Lang;
use App\Core\Logger;
use App\Core\RateLimit;
use App\Core\Request;
use App\Core\Session;
use App\Core\Settings;
use App\Core\Validator;
use App\Mail\Mailer;
use Google\Cloud\Firestore\FieldValue;
use Google\Cloud\Firestore\Transaction;
use Kreait\Firebase\Exception\Auth\EmailExists;
use Kreait\Firebase\Exception\Auth\UserNotFound;

/**
 * Flow: register/login/forgot -> OTP e-mail -> verify-otp -> (session | reset-permission).
 * Pre-auth state lives in $_SESSION['pending'] (server side, bound to the browser session cookie).
 */
final class AuthService
{
    private const PENDING_MAX_AGE = 1800;

    private static function s(array $in, string $key, int $max = 256): string
    {
        $v = $in[$key] ?? '';
        return is_string($v) ? mb_substr($v, 0, $max) : '';
    }

    private static function userRef(string $uid)
    {
        return Firebase::db()->collection('users')->document($uid);
    }

    // ------------------------------------------------------------------ register
    public static function register(array $in): array
    {
        if (Settings::get('maintenance')) { throw new ApiException('MAINTENANCE', 503); }
        RateLimit::hit('register_ip:' . Request::ip(), 10, 3600);

        $name  = Validator::name(self::s($in, 'fullName', 120));
        $email = Validator::email(self::s($in, 'email', 254));
        $pw    = self::s($in, 'password', 129);
        $pw2   = self::s($in, 'confirmPassword', 129);

        $err = [];
        if ($name === null) { $err['fullName'] = 'FIELD_NAME_INVALID'; }
        if ($email === null) { $err['email'] = 'FIELD_EMAIL_INVALID'; }
        if (!Validator::passwordStrong($pw, $email)) { $err['password'] = 'FIELD_PASSWORD_WEAK'; }
        elseif ($pw !== $pw2) { $err['confirmPassword'] = 'FIELD_PASSWORD_MISMATCH'; }
        Validator::failIf($err);

        Turnstile::verify(self::s($in, 'turnstileToken', 2048));

        $auth = Firebase::auth();
        try {
            $auth->getUserByEmail($email);
            throw new ApiException('EMAIL_EXISTS', 409);
        } catch (UserNotFound) {
            // free to register
        }

        try {
            $fb = $auth->createUser([
                'email' => $email, 'password' => $pw, 'displayName' => $name,
                'emailVerified' => false, 'disabled' => false,
            ]);
        } catch (EmailExists) {
            throw new ApiException('EMAIL_EXISTS', 409);
        }
        $uid = $fb->uid;

        try {
            $db = Firebase::db();
            $db->runTransaction(function (Transaction $t) use ($db, $uid, $name, $email) {
                $t->create($db->collection('users')->document($uid), [
                    'fullName' => $name, 'email' => $email, 'emailLower' => $email,
                    'status' => 'active', 'emailVerified' => false,
                    'avatar' => ['type' => 'preset', 'key' => 'default'],
                    'lang' => Lang::current(), 'theme' => Config::get('app.default_theme'),
                    'createdAt' => FieldValue::serverTimestamp(), 'lastLoginAt' => null,
                ]);
                $t->create($db->collection('wallets')->document($uid), [
                    'balance' => 0, 'held' => 0, 'totalDeposit' => 0, 'totalWithdraw' => 0,
                    'version' => 0, 'updatedAt' => FieldValue::serverTimestamp(),
                ]);
            });
        } catch (\Throwable $e) {
            try { $auth->deleteUser($uid); } catch (\Throwable) {}   // no half-created accounts
            throw $e;
        }

        AuditLog::log($uid, 'user.registered', "users/{$uid}");
        return self::sendOtpFor('register', $email, $uid, 'REGISTER_CODE_SENT');
    }

    // --------------------------------------------------------------------- login
    public static function login(array $in): array
    {
        $ip = Request::ip();
        RateLimit::hit("login_ip:{$ip}", 40, 900);

        $email = Validator::email(self::s($in, 'email', 254));
        $pw    = self::s($in, 'password', 129);
        if ($email === null || $pw === '' || strlen($pw) > 128) {
            throw new ApiException('INVALID_CREDENTIALS', 401);
        }
        $ek = 'login_fail_email:' . hash('sha256', $email);
        $ik = "login_fail_ip:{$ip}";
        RateLimit::assertNotBlocked($ek, 5);
        RateLimit::assertNotBlocked($ik, 20);

        Turnstile::verify(self::s($in, 'turnstileToken', 2048));

        try {
            $uid = self::passwordSignIn($email, $pw);
        } catch (ApiException $e) {
            if ($e->errorCode === 'INVALID_CREDENTIALS') {
                RateLimit::record($ek, 900);
                RateLimit::record($ik, 900);
            }
            throw $e;
        }
        RateLimit::clear($ek);

        $snap = self::userRef($uid)->snapshot();
        if (!$snap->exists()) {
            Logger::error('login.profile_missing', ['uid' => $uid]);
            throw new ApiException('INVALID_CREDENTIALS', 401);
        }
        $u = $snap->data();
        if (($u['status'] ?? '') !== 'active') {
            throw new ApiException('ACCOUNT_DISABLED', 403);
        }
        $purpose = !empty($u['emailVerified']) ? 'login' : 'register';

        // ── Remember-me: skip OTP if device is trusted (login only, not first-time register) ──
        if ($purpose === 'login' && !empty($in['rememberMe'])) {
            $deviceId = self::s($in, 'deviceId', 128);
            if ($deviceId !== '' && self::isTrustedDevice($uid, $deviceId)) {
                // device trusted → skip OTP, login directly
                $_SESSION['pendingRememberMe'] = true; // mark so verifyOtp can trust again
                return self::completeLogin($uid, false);
            }
        }
        // Store rememberMe flag so after OTP verify we can mark device trusted
        if ($purpose === 'login' && !empty($in['rememberMe'])) {
            $deviceId = self::s($in, 'deviceId', 128);
            $_SESSION['pendingRememberMe'] = $deviceId ?: '';
        }

        return self::sendOtpFor($purpose, $email, $uid, $purpose === 'login' ? 'LOGIN_CODE_SENT' : 'REGISTER_CODE_SENT');
    }

    /**
     * Admin login: email + password only, NO email code (OTP).
     * Only accounts with an active admin_users doc may use it; everyone else
     * gets NOT_ADMIN. Same rate limits + Turnstile as the normal login.
     */
    public static function adminLogin(array $in): array
    {
        $ip = Request::ip();
        RateLimit::hit("login_ip:{$ip}", 40, 900);

        $email = Validator::email(self::s($in, 'email', 254));
        $pw    = self::s($in, 'password', 129);
        if ($email === null || $pw === '' || strlen($pw) > 128) {
            throw new ApiException('INVALID_CREDENTIALS', 401);
        }
        $ek = 'login_fail_email:' . hash('sha256', $email);
        $ik = "login_fail_ip:{$ip}";
        RateLimit::assertNotBlocked($ek, 5);
        RateLimit::assertNotBlocked($ik, 20);

        Turnstile::verify(self::s($in, 'turnstileToken', 2048));

        try {
            $uid = self::passwordSignIn($email, $pw);
        } catch (ApiException $e) {
            if ($e->errorCode === 'INVALID_CREDENTIALS') {
                RateLimit::record($ek, 900);
                RateLimit::record($ik, 900);
            }
            throw $e;
        }
        RateLimit::clear($ek);

        $snap = self::userRef($uid)->snapshot();
        if (!$snap->exists()) {
            throw new ApiException('INVALID_CREDENTIALS', 401);
        }
        $u = $snap->data();
        if (($u['status'] ?? '') !== 'active') {
            throw new ApiException('ACCOUNT_DISABLED', 403);
        }
        if (!AdminGuard::isAdmin($uid)) {
            throw new ApiException('NOT_ADMIN', 403);
        }
        return self::completeLogin($uid, false);
    }

    // ── Trusted device helpers ──
    private static function isTrustedDevice(string $uid, string $deviceId): bool
    {
        try {
            $ref = Firebase::db()->collection('trusted_devices')->document($uid)
                ->collection('devices')->document($deviceId);
            $snap = $ref->snapshot();
            if (!$snap->exists()) { return false; }
            $d = $snap->data();
            $exp = (int)($d['expiresAt'] ?? 0);
            if ($exp < time()) {
                $ref->delete(); // cleanup expired
                return false;
            }
            return true;
        } catch (\Throwable) { return false; }
    }

    private static function trustDevice(string $uid, string $deviceId): void
    {
        if ($deviceId === '') { return; }
        try {
            Firebase::db()->collection('trusted_devices')->document($uid)
                ->collection('devices')->document($deviceId)->set([
                    'createdAt' => FieldValue::serverTimestamp(),
                    'expiresAt' => time() + 7 * 86400, // 7 days
                    'uid' => $uid,
                ]);
        } catch (\Throwable) {}
    }

    /** Re-authentication inside the app (profile, admin). Wrong password => WRONG_PASSWORD. */
    public static function verifyPassword(string $uid, string $email, string $password): void
    {
        RateLimit::assertNotBlocked("pw_fail:{$uid}", 5);
        try {
            $got = self::passwordSignIn($email, $password);
        } catch (ApiException $e) {
            if ($e->errorCode === 'INVALID_CREDENTIALS') {
                RateLimit::record("pw_fail:{$uid}", 900);
                throw new ApiException('WRONG_PASSWORD', 400);
            }
            throw $e;
        }
        if ($got !== $uid) { throw new ApiException('WRONG_PASSWORD', 400); }
        RateLimit::clear("pw_fail:{$uid}");
    }

    /** Password check via Firebase Auth REST (Admin SDK cannot verify passwords). Token is discarded. */
    private static function passwordSignIn(string $email, string $password): string
    {
        $key = (string)Config::get('firebase.web_api_key', '');
        if ($key === '') {
            Logger::error('firebase.web_api_key_missing');
            throw new ApiException('SERVICE_UNAVAILABLE', 503);
        }
        $ch = curl_init('https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=' . rawurlencode($key));
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['email' => $email, 'password' => $password, 'returnSecureToken' => false]),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $raw = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $j = is_string($raw) ? json_decode($raw, true) : null;

        if ($http === 200 && is_array($j) && !empty($j['localId'])) {
            return (string)$j['localId'];
        }
        $msg = (string)($j['error']['message'] ?? '');
        if ($msg === 'USER_DISABLED') { throw new ApiException('ACCOUNT_DISABLED', 403); }
        if (str_starts_with($msg, 'TOO_MANY_ATTEMPTS')) { throw new ApiException('RATE_LIMITED', 429); }
        if (in_array($msg, ['INVALID_LOGIN_CREDENTIALS', 'INVALID_PASSWORD', 'EMAIL_NOT_FOUND', 'INVALID_EMAIL'], true)) {
            throw new ApiException('INVALID_CREDENTIALS', 401);
        }
        Logger::error('firebase.signin_failed', ['http' => $http, 'msg' => $msg]);
        throw new ApiException('SERVICE_UNAVAILABLE', 503);
    }

    // --------------------------------------------------------------- OTP plumbing
    private static function otpInfo(string $purpose, string $email): array
    {
        return [
            'next'      => 'verify',
            'purpose'   => $purpose,
            'email'     => self::maskEmail($email),
            'expiresIn' => (int)Config::get('otp.ttl'),
            'resendIn'  => (int)Config::get('otp.resend_after'),
            'codeLength'=> (int)Config::get('otp.length'),
        ];
    }

    public static function maskEmail(string $e): string
    {
        [$l, $d] = array_pad(explode('@', $e, 2), 2, '');
        return mb_substr($l, 0, 2) . str_repeat('*', max(2, mb_strlen($l) - 2)) . '@' . $d;
    }

    private static function sendOtpFor(string $purpose, string $email, string $uid, string $okCode, ?string $prevId = null): array
    {
        $otp = OtpService::issue($purpose, $email, $uid, $prevId);
        $_SESSION['pending'] = [
            'otpId' => $otp['id'], 'purpose' => $purpose, 'uid' => $uid, 'email' => $email,
            'sentAt' => time(),
        ];
        if (!Mailer::sendOtp($email, $purpose, $otp['code'], $otp['expiresIn'], Lang::current())) {
            throw new ApiException('MAIL_FAILED', 502, self::otpInfo($purpose, $email)); // user may press "resend"
        }
        return [$okCode, self::otpInfo($purpose, $email)];
    }

    private static function pending(): array
    {
        $p = $_SESSION['pending'] ?? null;
        if (!is_array($p) || time() - (int)$p['sentAt'] > self::PENDING_MAX_AGE) {
            unset($_SESSION['pending']);
            throw new ApiException('NO_PENDING', 400);
        }
        return $p;
    }

    // ----------------------------------------------------------------- verify OTP
    public static function verifyOtp(array $in): array
    {
        $p = self::pending();
        RateLimit::hit('otp_verify_ip:' . Request::ip(), 30, 900);
        $code = preg_replace('/\D/', '', self::s($in, 'code', 12)) ?? '';

        $r = OtpService::verify((string)$p['otpId'], (string)$p['purpose'], $code);
        if ($r['status'] !== 'ok') {
            throw new ApiException($r['status'], 400, ['attemptsLeft' => $r['attemptsLeft']]);
        }

        if ($p['purpose'] === 'reset') {
            if (empty($p['uid'])) { throw new ApiException('OTP_INVALID', 400); }
            Session::regenerate();
            $_SESSION['reset'] = ['uid' => $p['uid'], 'email' => $p['email'], 'until' => time() + 600];
            unset($_SESSION['pending']);
            return ['OTP_VERIFIED_RESET', ['next' => 'reset-password']];
        }
        if (!in_array($p['purpose'], ['register', 'login'], true) || empty($p['uid'])) {
            throw new ApiException('BAD_REQUEST', 400);
        }
        // After OTP verified: if rememberMe was set, trust the device
        $pendingDevice = $_SESSION['pendingRememberMe'] ?? null;
        $result = self::completeLogin((string)$p['uid'], $p['purpose'] === 'register');
        if (is_string($pendingDevice) && $pendingDevice !== '') {
            self::trustDevice((string)$p['uid'], $pendingDevice);
        }
        unset($_SESSION['pendingRememberMe']);
        return $result;
    }

    private static function completeLogin(string $uid, bool $markVerified): array
    {
        $ref = self::userRef($uid);
        $snap = $ref->snapshot();
        if (!$snap->exists() || ($snap->data()['status'] ?? '') !== 'active') {
            throw new ApiException('ACCOUNT_DISABLED', 403);
        }
        if (Settings::get('maintenance') && !AdminGuard::isAdmin($uid)) {
            throw new ApiException('MAINTENANCE', 503);
        }
        if ($markVerified) {
            Firebase::auth()->updateUser($uid, ['emailVerified' => true]);
            $ref->update([['path' => 'emailVerified', 'value' => true]]);
        }
        $ref->update([['path' => 'lastLoginAt', 'value' => FieldValue::serverTimestamp()]]);

        Session::regenerate();                       // new session id on privilege change
        unset($_SESSION['pending']);
        $_SESSION['uid'] = $uid;
        $_SESSION['authAt'] = time();
        unset($_SESSION['lastCheck'], $_SESSION['userCache'], $_SESSION['pendingRememberMe']);
        AuditLog::log($uid, $markVerified ? 'user.verified_and_logged_in' : 'user.logged_in', "users/{$uid}");
        return [$markVerified ? 'ACCOUNT_VERIFIED' : 'LOGIN_OK', ['next' => 'dashboard']];
    }

    // ------------------------------------------------------------------ resend OTP
    public static function resendOtp(): array
    {
        $p = self::pending();
        $wait = (int)Config::get('otp.resend_after') - (time() - (int)$p['sentAt']);
        if ($wait > 0) {
            throw new ApiException('OTP_COOLDOWN', 429, ['retryAfter' => $wait]);
        }
        RateLimit::hit('otp_resend_ip:' . Request::ip(), 15, 3600);

        if (empty($p['uid'])) {   // forgot-password for an unknown e-mail: behave identically
            $_SESSION['pending']['sentAt'] = time();
            return ['CODE_RESENT', self::otpInfo('reset', (string)$p['email'])];
        }
        return self::sendOtpFor((string)$p['purpose'], (string)$p['email'], (string)$p['uid'], 'CODE_RESENT', (string)$p['otpId']);
    }

    // --------------------------------------------------------------- forgot / reset
    public static function forgotPassword(array $in): array
    {
        RateLimit::hit('forgot_ip:' . Request::ip(), 10, 3600);
        $email = Validator::email(self::s($in, 'email', 254));
        Validator::failIf($email === null ? ['email' => 'FIELD_EMAIL_INVALID'] : []);
        RateLimit::hit('forgot_email:' . hash('sha256', $email), 3, 3600);

        $uid = null;
        try {
            $fb = Firebase::auth()->getUserByEmail($email);
            $snap = self::userRef($fb->uid)->snapshot();
            if ($snap->exists() && ($snap->data()['status'] ?? '') === 'active') {
                $uid = $fb->uid;
            }
        } catch (UserNotFound) {
            // fall through: same response either way (no account enumeration)
        }

        if ($uid === null) {
            $_SESSION['pending'] = [
                'otpId' => bin2hex(random_bytes(16)), 'purpose' => 'reset', 'uid' => null,
                'email' => $email, 'sentAt' => time(),
            ];
            return ['RESET_CODE_SENT', self::otpInfo('reset', $email)];
        }
        try {
            return self::sendOtpFor('reset', $email, $uid, 'RESET_CODE_SENT');
        } catch (ApiException $e) {
            if ($e->errorCode === 'MAIL_FAILED') {
                return ['RESET_CODE_SENT', self::otpInfo('reset', $email)];   // do not reveal; user can resend
            }
            throw $e;
        }
    }

    public static function resetPassword(array $in): array
    {
        $r = $_SESSION['reset'] ?? null;
        if (!is_array($r) || time() > (int)$r['until']) {
            unset($_SESSION['reset']);
            throw new ApiException('RESET_SESSION_INVALID', 400);
        }
        $pw  = self::s($in, 'newPassword', 129);
        $pw2 = self::s($in, 'confirmPassword', 129);
        $err = [];
        if (!Validator::passwordStrong($pw, (string)$r['email'])) { $err['newPassword'] = 'FIELD_PASSWORD_WEAK'; }
        elseif ($pw !== $pw2) { $err['confirmPassword'] = 'FIELD_PASSWORD_MISMATCH'; }
        Validator::failIf($err);

        $uid = (string)$r['uid'];
        $auth = Firebase::auth();
        $auth->changeUserPassword($uid, $pw);
        $auth->updateUser($uid, ['emailVerified' => true]);     // OTP proved mailbox ownership
        $auth->revokeRefreshTokens($uid);
        self::userRef($uid)->update([['path' => 'emailVerified', 'value' => true]]);
        self::revokeAllSessions($uid);

        unset($_SESSION['reset']);
        Session::regenerate();
        AuditLog::log($uid, 'user.password_reset', "users/{$uid}");
        Mailer::sendSecurityNotice((string)$r['email'], 'mail.security.password_changed', Lang::current());
        return ['RESET_OK', ['next' => 'login']];
    }

    /** Delete every stored session of a user (password reset, account disable, ...). */
    public static function revokeAllSessions(string $uid): void
    {
        $docs = Firebase::db()->collection('sessions')->where('userId', '=', $uid)->documents();
        foreach ($docs as $d) {
            $d->reference()->delete();
        }
    }

    // --------------------------------------------------------- authenticated user
    /** @return array{uid:string,fullName:string,email:string} */
    public static function requireUser(): array
    {
        $uid = $_SESSION['uid'] ?? null;
        if (!is_string($uid) || $uid === '') {
            throw new ApiException('UNAUTHENTICATED', 401);
        }
        $now = time();
        if (empty($_SESSION['userCache']) || $now - (int)($_SESSION['lastCheck'] ?? 0) > 60) {
            $snap = self::userRef($uid)->snapshot();
            $u = $snap->exists() ? $snap->data() : null;
            if ($u === null || ($u['status'] ?? '') !== 'active' || empty($u['emailVerified'])) {
                $disabled = $u !== null && ($u['status'] ?? '') !== 'active';
                Session::destroy();
                throw new ApiException($disabled ? 'ACCOUNT_DISABLED' : 'UNAUTHENTICATED', $disabled ? 403 : 401);
            }
            $_SESSION['userCache'] = ['uid' => $uid, 'fullName' => (string)$u['fullName'], 'email' => (string)$u['email']];
            $_SESSION['lastCheck'] = $now;
        }
        return $_SESSION['userCache'];
    }

    public static function logout(): array
    {
        $uid = $_SESSION['uid'] ?? null;
        if (is_string($uid)) { AuditLog::log($uid, 'user.logged_out', "users/{$uid}"); }
        Session::destroy();
        return ['LOGOUT_OK', []];
    }
}
