<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Firebase;
use App\Core\Logger;
use App\Core\Money;
use App\Core\Time;
use Google\Cloud\Firestore\Transaction;

/**
 * Asks TashPay what really happened to unfinished transactions. Every state change goes through
 * WalletService (idempotent), so running this concurrently with callbacks cannot double-credit.
 */
final class ReconcileService
{
    private const ACTIVE = ['pending', 'processing'];

    /** Check one transaction (array from WalletService::tx). */
    public static function check(array $tx): void
    {
        try {
            WalletService::touchQuery($tx['id']);
            $tx['type'] === 'deposit' ? self::checkDeposit($tx) : self::checkWithdraw($tx);
        } catch (TashPayUnreachable) {
            // try again next run
        } catch (\Throwable $e) {
            Logger::error('reconcile.item_failed', ['tx' => $tx['id'], 'e' => $e->getMessage()]);
        }
    }

    private static function checkDeposit(array $tx): void
    {
        $r = TashPay::post('/repay/result', ['merchantRef' => $tx['merchantOrderId']]);
        if ($r['code'] === 0 && (int)($r['data']['status'] ?? 0) === 1) {
            $actual = Money::parseDecimal((string)($r['data']['actualAmount'] ?? $r['data']['amount'] ?? ''));
            if ($actual !== null) {
                $c = WalletService::creditDeposit($tx['id'], $actual, (string)($r['data']['transactionId'] ?? ''));
                if ($c['result'] === 'credited') { PaymentService::afterDeposit((string)$c['uid'], (int)$c['net']); }
            }
            return;
        }
        // Still unpaid after 24 h: close it (a late payment would still be credited by callback/query).
        if (Time::ms() - (int)$tx['createdAtMs'] > 24 * 3600 * 1000) {
            WalletService::cancelDeposit($tx['id'], 'expired_unpaid');
        }
    }

    private static function checkWithdraw(array $tx): void
    {
        $r = TashPay::post('/withdraw/result', ['merchantRef' => $tx['merchantOrderId']]);
        if ($r['code'] === 0) {
            $d = $r['data'] ?? [];
            $st = (int)($d['status'] ?? -1);
            if ($st === 1) {
                $s = WalletService::settleWithdraw($tx['id'], (string)($d['flowId'] ?? ''), Money::parseDecimal((string)($d['fee'] ?? '')));
                if ($s['result'] === 'settled') { PaymentService::afterWithdraw((string)$s['uid'], 'success', (int)$s['amount']); }
            } elseif ($st === 2) {
                $f = WalletService::failWithdraw($tx['id'], (string)($d['errorMsg'] ?? 'failed'));
                if ($f['result'] === 'refunded') { PaymentService::afterWithdraw((string)$f['uid'], 'failed', (int)$f['total']); }
            } elseif ($st === 0) {
                WalletService::markWithdrawProcessing($tx['id'], isset($d['transactionId']) ? (string)$d['transactionId'] : null);
            }
            return;
        }
        // 5008 = TashPay has no such order. If it never reached them (still "pending" after 5 min) the hold is returned.
        if ($r['code'] === 5008 && $tx['status'] === 'pending' && Time::ms() - (int)$tx['createdAtMs'] > 5 * 60 * 1000) {
            $f = WalletService::failWithdraw($tx['id'], 'never_reached_gateway');
            if ($f['result'] === 'refunded') { PaymentService::afterWithdraw((string)$f['uid'], 'failed', (int)$f['total']); }
        }
    }

    /** Cron entry. Returns counters. */
    public static function run(int $batch = 40): array
    {
        $stats = ['deposits' => 0, 'withdraws' => 0, 'cleaned' => 0];
        if (!self::lock()) { return $stats + ['skipped' => true]; }
        $now = Time::ms();
        foreach (self::ACTIVE as $status) {
            $docs = Firebase::db()->collection('transactions')->where('status', '=', $status)
                ->orderBy('createdAtMs', 'ASC')->limit($batch)->documents();
            foreach ($docs as $doc) {
                $tx = $doc->data() + ['id' => $doc->id()];
                if ($now - (int)$tx['createdAtMs'] < 60_000) { continue; }
                self::check($tx);
                $tx['type'] === 'deposit' ? $stats['deposits']++ : $stats['withdraws']++;
            }
        }
        $stats['cleaned'] = self::cleanup();
        return $stats;
    }

    /** Single-runner lock in Firestore (expires after 4 min). */
    private static function lock(): bool
    {
        $ref = Firebase::db()->collection('settings')->document('cron_lock');
        $ok = false;
        Firebase::db()->runTransaction(function (Transaction $t) use ($ref, &$ok) {
            $ok = false;
            $s = $t->snapshot($ref);
            $until = $s->exists() ? (int)($s->data()['until'] ?? 0) : 0;
            if ($until < time()) {
                $t->set($ref, ['until' => time() + 240]);
                $ok = true;
            }
        });
        return $ok;
    }

    /** Remove expired OTPs, sessions and rate-limit counters. */
    public static function cleanup(int $limit = 300): int
    {
        $n = 0; $now = time();
        foreach (['otp_requests' => $now - 3600, 'sessions' => $now, 'rate_limits' => $now] as $col => $before) {
            $docs = Firebase::db()->collection($col)->where('expiresAt', '<', $before)->limit($limit)->documents();
            foreach ($docs as $d) { $d->reference()->delete(); $n++; }
        }
        return $n;
    }
}
