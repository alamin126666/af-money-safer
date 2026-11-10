<?php
declare(strict_types=1);

// External scheduler entry (e.g. cron-job.org). Does the same work as scripts/reconcile.php:
// pending deposit/withdraw checks against TashPay + cleanup. Optionally protected by CRON_SECRET (header).
// Safe to call every minute: a Firestore lock stops overlapping runs, and every balance change is idempotent.
require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Config;
use App\Core\Logger;
use App\Core\Request;
use App\Services\ReconcileService;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// CRON_SECRET is optional. If it is set, callers must send it; if it is empty the endpoint is open.
// Open mode is acceptable because the work is idempotent, a Firestore lock allows at most one run per ~4 minutes,
// and money moves only when TashPay itself confirms a payment.
$secret = (string)Config::get('cron_secret', '');
if ($secret !== '') {
    $given = Request::header('X-Cron-Secret') ?? '';
    if ($given === '' && preg_match('/^Bearer (.+)$/', (string)Request::header('Authorization'), $m)) { $given = $m[1]; }
    if (!hash_equals($secret, $given)) {
        Logger::warn('cron.unauthorized', ['ip' => Request::ip()]);
        http_response_code(403); echo '{"ok":false}'; exit;
    }
}

try {
    set_time_limit(50);
    $stats = ReconcileService::run();
    Logger::info('cron.run', $stats);
    echo json_encode(['ok' => true] + $stats);
} catch (\Throwable $e) {
    Logger::error('cron.run_failed', ['e' => $e->getMessage()]);
    http_response_code(500); echo '{"ok":false}';
}
