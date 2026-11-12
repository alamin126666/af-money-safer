<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\ApiException;
use App\Core\RateLimit;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\TotpService;

Api::handle(function ($u) {
    $in = Request::json();
    RateLimit::hit("totp_setup:{$u['uid']}", 5, 3600);
    AuthService::verifyPassword($u['uid'], $u['email'], is_string($in['password'] ?? null) ? $in['password'] : '');
    if (TotpService::enabledFor($u['uid'])) { throw new ApiException('TOTP_ALREADY_ENABLED', 409); }
    $secret = TotpService::generateSecret();
    // Stored in the session only — the user doc is updated after the code is verified.
    $_SESSION['totp_setup'] = ['secret' => $secret, 'at' => time()];
    return ['TOTP_SETUP', ['secret' => $secret, 'otpauthUrl' => TotpService::otpauthUrl($secret, $u['email'])]];
}, ['auth' => true]);
