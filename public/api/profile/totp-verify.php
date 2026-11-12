<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\ApiException;
use App\Core\Request;
use App\Services\AuditLog;
use App\Services\TotpService;

Api::handle(function ($u) {
    $in = Request::json();
    $s = $_SESSION['totp_setup'] ?? null;
    if (!is_array($s) || time() - (int)$s['at'] > 600) { unset($_SESSION['totp_setup']); throw new ApiException('NO_PENDING', 400); }
    $code = preg_replace('/\D/', '', is_string($in['code'] ?? null) ? $in['code'] : '') ?? '';
    if (!TotpService::verify((string)$s['secret'], $code)) { throw new ApiException('TOTP_INVALID', 400); }
    TotpService::enable($u['uid'], (string)$s['secret']);
    unset($_SESSION['totp_setup']);
    AuditLog::log($u['uid'], 'profile.totp_enabled', "users/{$u['uid']}");
    return ['TOTP_ENABLED', []];
}, ['auth' => true]);
