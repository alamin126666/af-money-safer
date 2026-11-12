<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\ApiException;
use App\Core\Request;
use App\Services\AuditLog;
use App\Services\AuthService;
use App\Services\TotpService;

Api::handle(function ($u) {
    $in = Request::json();
    AuthService::verifyPassword($u['uid'], $u['email'], is_string($in['password'] ?? null) ? $in['password'] : '');
    $code = preg_replace('/\D/', '', is_string($in['code'] ?? null) ? $in['code'] : '') ?? '';
    $secret = TotpService::secretFor($u['uid']);
    if ($secret === null || !TotpService::verify($secret, $code)) { throw new ApiException('TOTP_INVALID', 400); }
    TotpService::disable($u['uid']);
    AuditLog::log($u['uid'], 'profile.totp_disabled', "users/{$u['uid']}");
    return ['TOTP_DISABLED', []];
}, ['auth' => true]);
