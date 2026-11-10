<?php
declare(strict_types=1);

// TashPay server callback. No session, no CSRF: authenticity = MD5 signature + merchant account (TashPay doc).
// Replies exactly "SUCCESS" only after the result is stored; anything else makes TashPay retry (max 5).
require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Config;
use App\Core\Logger;
use App\Core\Request;
use App\Services\CallbackRejected;
use App\Services\PaymentService;

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

if (Request::method() !== 'POST') { http_response_code(405); echo 'FAIL'; exit; }

$ips = (array)Config::get('tashpay.callback_ips', []);
if ($ips && !in_array(Request::ip(), $ips, true)) {
    // Signature is the real check. Set TASHPAY_ENFORCE_CALLBACK_IP=true to also reject unknown sources.
    Logger::warn('notify.unexpected_ip', ['ip' => Request::ip()]);
    if (Config::get('tashpay.enforce_ip')) { http_response_code(403); echo 'FAIL'; exit; }
}

$raw = file_get_contents('php://input', false, null, 0, 65537);
$p = ($raw !== false && strlen($raw) <= 65536) ? json_decode($raw, true) : null;
if (!is_array($p)) { http_response_code(400); echo 'FAIL'; exit; }

try {
    echo PaymentService::handleDepositCallback($p);
} catch (CallbackRejected $e) {
    Logger::warn('notify.rejected', ['dir' => 'deposit', 'why' => $e->getMessage(), 'order' => $p['orderId'] ?? null]);
    http_response_code(400); echo 'FAIL';
} catch (\Throwable $e) {
    Logger::error('notify.error', ['dir' => 'deposit', 'e' => $e->getMessage()]);
    http_response_code(500); echo 'FAIL';      // TashPay will retry
}
