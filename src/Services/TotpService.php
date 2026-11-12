<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Firebase;
use App\Core\Time;

/**
 * TOTP (RFC 6238) for Authenticator apps — replaces the e-mail OTP at login
 * and at withdraw when the user enables 2FA. No QR library exists and none
 * is used: the setup screen shows the secret and the otpauth:// URL as
 * copyable text, so the user pastes it into their Authenticator app.
 * Secrets live only on the user doc (server-side); the client never sees
 * a secret except during the one-time setup flow.
 */
final class TotpService
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const ISSUER = 'AF MONEY SAFER';

    private const B32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(20));   // 160 bits -> 32 chars
    }

    public static function base32Encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $c) { $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT); }
        $out = '';
        for ($i = 0; $i < strlen($bits); $i += 5) {
            $chunk = substr($bits, $i, 5);
            $out .= self::B32[bindec(str_pad($chunk, 5, '0'))];
        }
        return $out;
    }

    public static function base32Decode(string $s): ?string
    {
        $s = strtoupper(preg_replace('/[^A-Z2-7]/', '', $s) ?? '');
        if ($s === '' || strlen($s) % 8 !== 0) { return null; }
        $bits = '';
        foreach (str_split($s) as $c) {
            $pos = strpos(self::B32, $c);
            if ($pos === false) { return null; }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) { $out .= chr(bindec(substr($bits, $i, 8))); }
        return $out;
    }

    public static function otpauthUrl(string $secret, string $email): string
    {
        return 'otpauth://totp/' . rawurlencode(self::ISSUER) . ':' . rawurlencode($email)
            . '?secret=' . $secret . '&issuer=' . rawurlencode(self::ISSUER)
            . '&period=' . self::PERIOD . '&digits=' . self::DIGITS;
    }

    /** Code for one 30s step: HMAC-SHA1, dynamic truncation, mod 10^6. */
    public static function codeAt(string $secret, int $time): ?string
    {
        $key = self::base32Decode($secret);
        if ($key === null || $key === '') { return null; }
        $counter = intdiv($time, self::PERIOD);
        $msg = pack('N2', $counter >> 32, $counter & 0xFFFFFFFF);
        $hash = hash_hmac('sha1', $msg, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $bin = ((ord($hash[$offset]) & 0x7F) << 24)
             | ((ord($hash[$offset + 1]) & 0xFF) << 16)
             | ((ord($hash[$offset + 2]) & 0xFF) << 8)
             | (ord($hash[$offset + 3]) & 0xFF);
        return str_pad((string)($bin % 1000000), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /** Accept the current step and one step either side (clock skew). */
    public static function verify(string $secret, string $code, int $window = 1, ?int $now = null): bool
    {
        if (!preg_match('/^\d{6}$/', $code)) { return false; }
        $now ??= time();
        for ($i = -$window; $i <= $window; $i++) {
            $expect = self::codeAt($secret, $now + $i * self::PERIOD);
            if ($expect !== null && hash_equals($expect, $code)) { return true; }
        }
        return false;
    }

    // ------------------------------------------------------------- user doc
    public static function enabledFor(string $uid): bool
    {
        $s = Firebase::db()->collection('users')->document($uid)->snapshot();
        return $s->exists() && !empty($s->data()['totpEnabled']);
    }

    public static function secretFor(string $uid): ?string
    {
        $s = Firebase::db()->collection('users')->document($uid)->snapshot();
        if (!$s->exists()) { return null; }
        $v = $s->data()['totpSecret'] ?? null;
        return is_string($v) && $v !== '' ? $v : null;
    }

    public static function enable(string $uid, string $secret): void
    {
        Firebase::db()->collection('users')->document($uid)->update([
            ['path' => 'totpSecret', 'value' => $secret],
            ['path' => 'totpEnabled', 'value' => true],
            ['path' => 'totpEnabledAtMs', 'value' => Time::ms()],
        ]);
    }

    public static function disable(string $uid): void
    {
        Firebase::db()->collection('users')->document($uid)->update([
            ['path' => 'totpSecret', 'value' => null],
            ['path' => 'totpEnabled', 'value' => false],
            ['path' => 'totpEnabledAtMs', 'value' => null],
        ]);
    }
}
