<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $cfg = Config::get('session');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        session_name($cfg['cookie']['secure'] ? '__Host-afms' : 'afms');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $cfg['cookie']['secure'],
            'httponly' => true,
            'samesite' => $cfg['cookie']['samesite'],
        ]);
        session_set_save_handler(new FirestoreSessionHandler((int)$cfg['lifetime']), true);
        session_start();

        $now = time();
        if (isset($_SESSION['_created']) && $now - (int)$_SESSION['_created'] > 12 * 3600) {
            self::destroy();      // absolute lifetime: 12 h
            session_start();
        }
        $_SESSION['_created'] ??= $now;
    }

    /** New session id after any privilege change (login, OTP success, logout). */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
        Csrf::rotate();
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600, 'path' => '/', 'secure' => $p['secure'],
            'httponly' => true, 'samesite' => $p['samesite'],
        ]);
    }
}
