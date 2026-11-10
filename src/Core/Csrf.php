<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function rotate(): void
    {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    public static function verify(): void
    {
        $sent = Request::header('X-CSRF-Token') ?? '';
        if ($sent === '' || !hash_equals(self::token(), $sent)) {
            throw new ApiException('CSRF_INVALID', 403);
        }
    }
}
