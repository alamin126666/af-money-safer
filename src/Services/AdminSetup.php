<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Core\Config;
use App\Core\Firebase;
use App\Core\Lang;
use App\Core\RateLimit;
use App\Core\Request;
use App\Core\Session;
use App\Core\Time;
use App\Core\Validator;
use Google\Cloud\Firestore\FieldValue;
use Google\Cloud\Firestore\Transaction;
use Kreait\Firebase\Exception\Auth\EmailExists;
use Kreait\Firebase\Exception\Auth\UserNotFound;

/**
 * One-time admin setup (no Shell needed, works on the Render free plan).
 *
 * POST /api/admin/setup-account.php with { email, password, confirmPassword }:
 * creates the first admin account (Firebase Auth user + users/wallets docs +
 * admin_users super doc) and logs the browser in directly. After the first
 * admin exists the setup is closed for good: the setup page answers 404 and
 * this endpoint refuses with SETUP_CLOSED. A Firestore marker document makes
 * the "only once" check atomic, so two simultaneous setups cannot create two
 * admins. The only way to add more admins afterwards is the Admin panel
 * (or the token-gated /api/admin/setup.php endpoint).
 */
final class AdminSetup
{
    public static function alreadySetup(): bool
    {
        foreach (Firebase::db()->collection('admin_users')->limit(1)->documents() as $_) {
            return true;
        }
        return false;
    }

    private static function s(array $in, string $key, int $max = 256): string
    {
        $v = $in[$key] ?? '';
        return is_string($v) ? mb_substr(trim($v), 0, $max) : '';
    }

    public static function setup(array $in): array
    {
        RateLimit::hit('admin_setup_ip:' . Request::ip(), 5, 3600);

        $email = Validator::email(self::s($in, 'email', 254));
        $pw    = self::s($in, 'password', 129);
        $pw2   = self::s($in, 'confirmPassword', 129);

        $err = [];
        if ($email === null) { $err['email'] = 'FIELD_EMAIL_INVALID'; }
        if (!Validator::passwordStrong($pw, $email)) { $err['password'] = 'FIELD_PASSWORD_WEAK'; }
        elseif ($pw !== $pw2) { $err['confirmPassword'] = 'FIELD_PASSWORD_MISMATCH'; }
        Validator::failIf($err);

        Turnstile::verify(self::s($in, 'turnstileToken', 2048));

        if (self::alreadySetup()) { throw new ApiException('SETUP_CLOSED', 403); }

        $auth = Firebase::auth();
        try {
            $auth->getUserByEmail($email);
            throw new ApiException('EMAIL_EXISTS', 409);
        } catch (UserNotFound) {
            // free to register
        }

        try {
            $fb = $auth->createUser([
                'email' => $email, 'password' => $pw, 'displayName' => 'Admin',
                'emailVerified' => true, 'disabled' => false,
            ]);
        } catch (EmailExists) {
            throw new ApiException('EMAIL_EXISTS', 409);
        }
        $uid = $fb->uid;

        try {
            $db = Firebase::db();
            $db->runTransaction(function (Transaction $t) use ($db, $uid, $email) {
                $marker = $db->collection('settings')->document('admin_setup');
                if ($t->snapshot($marker)->exists()) { throw new ApiException('SETUP_CLOSED', 403); }
                $now = Time::ms();
                $t->create($db->collection('users')->document($uid), [
                    'fullName' => 'Admin', 'email' => $email, 'emailLower' => $email,
                    'status' => 'active', 'emailVerified' => true,
                    'avatar' => ['type' => 'preset', 'key' => 'default'],
                    'lang' => Lang::current(), 'theme' => Config::get('app.default_theme'),
                    'createdAt' => FieldValue::serverTimestamp(), 'lastLoginAt' => null,
                ]);
                $t->create($db->collection('wallets')->document($uid), [
                    'balance' => 0, 'held' => 0, 'totalDeposit' => 0, 'totalWithdraw' => 0,
                    'version' => 0, 'updatedAt' => FieldValue::serverTimestamp(),
                ]);
                $t->create($db->collection('admin_users')->document($uid), [
                    'email' => $email, 'role' => 'super', 'permissions' => [], 'active' => true,
                    'createdAtMs' => $now,
                ]);
                $t->create($marker, ['done' => true, 'uid' => $uid, 'createdAtMs' => $now]);
            });
        } catch (\Throwable $e) {
            try { $auth->deleteUser($uid); } catch (\Throwable) {}   // no half-created accounts
            throw $e;
        }

        // Log the new admin in directly (same session bootstrap as AuthService::completeLogin).
        Session::regenerate();
        unset($_SESSION['pending']);
        $_SESSION['uid'] = $uid;
        $_SESSION['authAt'] = time();
        unset($_SESSION['lastCheck'], $_SESSION['userCache'], $_SESSION['pendingRememberMe']);
        AuditLog::log($uid, 'admin.setup', 'settings/admin_setup');
        return ['ADMIN_SETUP_DONE', ['next' => '/admin/dashboard.php']];
    }
}
