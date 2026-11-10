<?php
declare(strict_types=1);

namespace App\Core;

final class Validator
{
    public static function email(string $v): ?string
    {
        $v = strtolower(trim($v));
        if ($v === '' || strlen($v) > 254 || !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        return $v;
    }

    public static function name(string $v): ?string
    {
        $v = trim(preg_replace('/\s+/u', ' ', $v) ?? '');
        $len = mb_strlen($v);
        if ($len < 2 || $len > 60 || !preg_match("/^[\\p{L}\\p{M}][\\p{L}\\p{M} .'\\-]*$/u", $v)) {
            return null;
        }
        return $v;
    }

    /** 8-128 chars with lower, upper and digit; must not contain the email's local part. */
    public static function passwordStrong(string $v, ?string $email = null): bool
    {
        $len = strlen($v);
        if ($len < 8 || $len > 128) { return false; }
        if (!preg_match('/[a-z]/', $v) || !preg_match('/[A-Z]/', $v) || !preg_match('/\d/', $v)) { return false; }
        if ($email !== null) {
            $local = strtolower(explode('@', $email)[0]);
            if (strlen($local) >= 4 && str_contains(strtolower($v), $local)) { return false; }
        }
        return true;
    }

    /** @param array<string,string> $errors field => message key */
    public static function failIf(array $errors): void
    {
        if ($errors) {
            $loc = [];
            foreach ($errors as $f => $key) { $loc[$f] = Lang::t($key); }
            throw new ApiException('VALIDATION_FAILED', 422, ['fields' => $loc]);
        }
    }
}
