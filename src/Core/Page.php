<?php
declare(strict_types=1);

namespace App\Core;

use App\Services\AdminGuard;
use App\Services\AuthService;

final class Page
{
    /**
     * @param array{auth?:bool,admin?:bool,app?:bool,js?:string[],css?:string[]} $o
     * Public pages never touch Firestore on the server; their JS fetches /api/auth/csrf.php.
     */
    public static function render(string $name, string $title, array $o = []): void
    {
        $o += ['auth' => false, 'admin' => false, 'app' => false, 'js' => [], 'css' => []];
        $csrf = null;
        if ($o['auth'] || $o['admin']) {
            Session::start();
            Lang::init();
            try { AuthService::requireUser(); } catch (ApiException) { self::redirect('/login.php'); }
            if ($o['admin'] && !AdminGuard::isAdminSession()) { self::redirect('/dashboard.php'); }
            $csrf = Csrf::token();
        }
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
        $root = dirname(__DIR__, 2);
        $v = static fn(string $f): string => (string)@filemtime($root . '/public' . $f);
        require $root . '/templates/layout.php';
    }

    public static function redirect(string $to): never
    {
        header('Location: ' . $to, true, 302);
        exit;
    }
}
