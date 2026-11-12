<?php
declare(strict_types=1);

namespace App\Core;

use App\Services\AdminGuard;
use App\Services\AuthService;

final class Page
{
    /**
     * @param array{auth?:bool,admin?:bool,app?:bool,js?:string[],css?:string[],layout?:string} $o
     * Public pages never touch Firestore on the server; their JS fetches /api/auth/csrf.php.
     * layout 'admin' renders templates/admin-layout.php (sidebar shell) instead of the user layout.
     */
    public static function render(string $name, string $title, array $o = []): void
    {
        $o += ['auth' => false, 'admin' => false, 'app' => false, 'js' => [], 'css' => [], 'layout' => 'default'];
        // Maintenance director: while maintenance is on, every user page sends
        // the visitor to /maintenance.php (reason + countdown) instead.
        // Exempt: the maintenance page itself, admin pages, and the splash —
        // the splash is the admin SPA's entry, so the client-side check in
        // core.js handles it (it exempts #/admin hashes).
        if ($name !== 'maintenance' && empty($o['admin']) && $o['layout'] !== 'admin' && $name !== 'index') {
            if ((bool)Settings::get('maintenance')) {
                Session::start();
                if (!AdminGuard::isAdminSession()) { self::redirect('/maintenance.php'); }
            }
        }
        if ($name === 'maintenance' && !(bool)Settings::get('maintenance')) {
            self::redirect('/login.php');
        }
        $csrf = null;
        $me = null;
        $adminRole = null;
        if ($o['auth'] || $o['admin']) {
            Session::start();
            Lang::init();
            try { $me = AuthService::requireUser(); } catch (ApiException) { self::redirect('/login.php'); }
            if ($o['admin'] && !AdminGuard::isAdminSession()) { self::redirect('/dashboard.php'); }
            if ($o['layout'] === 'admin' && $me !== null) { $adminRole = AdminGuard::adminDoc($me['uid'])['role'] ?? 'admin'; }
            $csrf = Csrf::token();
        }
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
        $root = dirname(__DIR__, 2);
        $v = static fn(string $f): string => (string)@filemtime($root . '/public' . $f);
        require $root . ($o['layout'] === 'admin' ? '/templates/admin-layout.php' : '/templates/layout.php');
    }

    public static function redirect(string $to): never
    {
        header('Location: ' . $to, true, 302);
        exit;
    }
}
