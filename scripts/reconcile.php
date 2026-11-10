<?php
declare(strict_types=1);

// Render Cron Job (every 5 min): `php /var/www/app/scripts/reconcile.php`
// 1) pending/processing deposits  -> TashPay /repay/result
// 2) pending/processing withdraws -> TashPay /withdraw/result
// 3) cleanup of expired OTPs, sessions and rate-limit counters
// A Firestore lock stops overlapping runs; all balance changes are idempotent (see WalletService).
require dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Logger;
use App\Services\ReconcileService;

try {
    $stats = ReconcileService::run();
    Logger::info('cron.reconcile', $stats);
    exit(0);
} catch (Throwable $e) {
    Logger::error('cron.reconcile_failed', ['e' => $e->getMessage()]);
    exit(1);
}
