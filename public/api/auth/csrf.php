<?php
declare(strict_types=1);

// GET: starts the session and hands the browser what it needs before any form is used.
// Only public values are returned (CSRF token, Turnstile *site* key, language, login state).
require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Lang;

Api::handle(fn() => ['OK', [
    'csrfToken'     => Csrf::token(),
    'turnstileSiteKey' => (string)Config::get('turnstile.site_key', ''),
    'lang'          => Lang::current(),
    'authenticated' => !empty($_SESSION['uid']),
    'otp'           => ['length' => (int)Config::get('otp.length'), 'resendIn' => (int)Config::get('otp.resend_after')],
]], ['methods' => ['GET'], 'csrf' => false]);
