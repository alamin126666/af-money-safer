<?php
declare(strict_types=1);

// GET: starts the session and hands the browser what it needs before any form is used.
// Only public values are returned (CSRF token, Turnstile *site* key, language, login state).
require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Lang;
use App\Services\AuthService;

Api::handle(function () {
    // A signed-in user whose account was disabled (e.g. by an admin while they
    // were already browsing) must be told: requireUser throws ACCOUNT_DISABLED
    // and destroys the session, so the 30s boot poll + every API call surface it.
    if (!empty($_SESSION['uid'])) {
        AuthService::requireUser();
    }
    return ['OK', [
    'csrfToken'     => Csrf::token(),
    'turnstileSiteKey' => (string)Config::get('turnstile.site_key', ''),
    'lang'          => Lang::current(),
    'authenticated' => !empty($_SESSION['uid']),
    'isAdmin'       => \App\Services\AdminGuard::isAdminSession(),
    'maintenance'   => [
        'on'      => (bool)\App\Core\Settings::get('maintenance'),
        'reason'  => (string)(\App\Core\Settings::get('maintenanceReason') ?? ''),
        'untilMs' => (int)(\App\Core\Settings::get('maintenanceUntilMs') ?? 0),
    ],
    'otp'           => ['length' => (int)Config::get('otp.length'), 'resendIn' => (int)Config::get('otp.resend_after')],
    ]];
}, ['methods' => ['GET'], 'csrf' => false, 'bypassMaintenance' => true]);
