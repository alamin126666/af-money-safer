<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Firebase;
use App\Core\Request;
use App\Core\RateLimit;
use Google\Cloud\Firestore\Transaction;

/**
 * Email OTP: 6 digits from random_int, stored only as HMAC-SHA256(id|purpose|code).
 * Single use (consumedAt), expiring, attempt-limited, resend-limited.
 * Purposes: register, login, reset (later: email_change, password_change, withdraw).
 */
final class OtpService
{
    public const PURPOSES = ['register', 'login', 'reset', 'email_change', 'password_change', 'withdraw'];

    public static function mac(string $id, string $purpose, string $code): string
    {
        return hash_hmac('sha256', "{$id}|{$purpose}|{$code}", (string)Config::get('app.secret'));
    }

    /** @return array{id:string,code:string,expiresIn:int} */
    public static function issue(string $purpose, string $email, ?string $uid, ?string $previousId = null): array
    {
        if (!in_array($purpose, self::PURPOSES, true)) {
            throw new \InvalidArgumentException('bad purpose');
        }
        RateLimit::hit('otp_send:' . $purpose . ':' . hash('sha256', $email), 5, 3600);

        $col = Firebase::db()->collection('otp_requests');
        if ($previousId !== null) {
            try {
                $col->document($previousId)->update([['path' => 'invalidatedAt', 'value' => time()]]);
            } catch (\Throwable) { /* previous doc already gone */ }
        }
        $ttl  = (int)Config::get('otp.ttl');
        $code = str_pad((string)random_int(0, 999999), (int)Config::get('otp.length'), '0', STR_PAD_LEFT);
        $id   = bin2hex(random_bytes(16));
        $now  = time();
        $col->document($id)->set([
            'purpose'       => $purpose,
            'userId'        => $uid,
            'codeHmac'      => self::mac($id, $purpose, $code),
            'attempts'      => 0,
            'maxAttempts'   => (int)Config::get('otp.max_attempts'),
            'createdAt'     => $now,
            'expiresAt'     => $now + $ttl,
            'consumedAt'    => null,
            'invalidatedAt' => null,
            'ipHash'        => Request::ipHash(),
        ]);
        return ['id' => $id, 'code' => $code, 'expiresIn' => $ttl];
    }

    /**
     * Atomic check. A correct code is consumed in the same transaction, so it can never be used twice
     * even with parallel requests.
     * @return array{status:string,attemptsLeft:int} status: ok|OTP_INVALID|OTP_EXPIRED|OTP_LOCKED|OTP_USED
     */
    public static function verify(string $id, string $purpose, string $code): array
    {
        if (!preg_match('/^[0-9a-f]{32}$/', $id) || !preg_match('/^\d{6}$/', $code)) {
            return ['status' => 'OTP_INVALID', 'attemptsLeft' => 0];
        }
        $ref = Firebase::db()->collection('otp_requests')->document($id);
        $result = ['status' => 'OTP_INVALID', 'attemptsLeft' => 0];

        Firebase::db()->runTransaction(function (Transaction $t) use ($ref, $purpose, $code, $id, &$result) {
            $snap = $t->snapshot($ref);
            if (!$snap->exists()) { $result = ['status' => 'OTP_INVALID', 'attemptsLeft' => 0]; return; }
            $d = $snap->data();
            if (($d['purpose'] ?? '') !== $purpose) { $result = ['status' => 'OTP_INVALID', 'attemptsLeft' => 0]; return; }
            if (!empty($d['consumedAt']) || !empty($d['invalidatedAt'])) { $result = ['status' => 'OTP_USED', 'attemptsLeft' => 0]; return; }
            if ((int)$d['expiresAt'] < time()) { $result = ['status' => 'OTP_EXPIRED', 'attemptsLeft' => 0]; return; }
            $attempts = (int)$d['attempts'];
            $max = (int)$d['maxAttempts'];
            if ($attempts >= $max) { $result = ['status' => 'OTP_LOCKED', 'attemptsLeft' => 0]; return; }

            if (hash_equals((string)$d['codeHmac'], self::mac($id, $purpose, $code))) {
                $t->update($ref, [['path' => 'consumedAt', 'value' => time()]]);
                $result = ['status' => 'ok', 'attemptsLeft' => $max - $attempts];
                return;
            }
            $attempts++;
            $t->update($ref, [['path' => 'attempts', 'value' => $attempts]]);
            $result = ['status' => $attempts >= $max ? 'OTP_LOCKED' : 'OTP_INVALID', 'attemptsLeft' => max(0, $max - $attempts)];
        });
        return $result;
    }
}
