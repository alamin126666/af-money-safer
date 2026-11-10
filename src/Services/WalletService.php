<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Core\Firebase;
use App\Core\Money;
use App\Core\Settings;
use App\Core\Time;
use Google\Cloud\Firestore\Transaction;

/**
 * The only code allowed to change a balance. Every change is ONE Firestore transaction that
 *  - re-reads wallet + transaction,
 *  - writes an immutable ledger entry with a deterministic id (so a repeat is a no-op),
 *  - updates wallet and transaction together.
 * All amounts are integer poisha.
 */
final class WalletService
{
    private static function col(string $n) { return Firebase::db()->collection($n); }
    private static function pairs(array $f): array
    {
        return array_map(static fn($k, $v) => ['path' => $k, 'value' => $v], array_keys($f), $f);
    }
    public static function id(): string { return bin2hex(random_bytes(10)); }
    public static function orderId(string $prefix): string   // <= 50 chars as required by TashPay
    {
        return $prefix . strtoupper(base_convert((string)time(), 10, 36)) . strtoupper(bin2hex(random_bytes(8)));
    }

    // ---------------------------------------------------------------- reads
    public static function wallet(string $uid): array
    {
        $s = self::col('wallets')->document($uid)->snapshot();
        $d = $s->exists() ? $s->data() : [];
        return ['balance' => (int)($d['balance'] ?? 0), 'held' => (int)($d['held'] ?? 0),
                'totalDeposit' => (int)($d['totalDeposit'] ?? 0), 'totalWithdraw' => (int)($d['totalWithdraw'] ?? 0)];
    }

    public static function tx(string $txId): ?array
    {
        $s = self::col('transactions')->document($txId)->snapshot();
        return $s->exists() ? $s->data() + ['id' => $txId] : null;
    }

    /** Safe view for the owner (no internal ids beyond what the UI shows). */
    public static function publicTx(array $d): array
    {
        $wn = (string)($d['walletNo'] ?? '');
        return [
            'id' => $d['id'], 'type' => $d['type'], 'method' => $d['method'], 'status' => $d['status'],
            'amount' => (int)$d['amount'], 'fee' => (int)($d['fee'] ?? 0), 'total' => (int)($d['total'] ?? $d['amount']),
            'orderId' => $d['merchantOrderId'], 'flowId' => $d['flowId'] ?? null,
            'walletNo' => $wn !== '' ? substr($wn, 0, 3) . '****' . substr($wn, -3) : null,
            'holderName' => $d['holderName'] ?? null,
            'createdAtMs' => (int)$d['createdAtMs'], 'updatedAtMs' => (int)$d['updatedAtMs'],
        ];
    }

    // -------------------------------------------------------------- deposits
    /** @return array{0:string,1:string} [txId, merchantOrderId] */
    public static function createDeposit(string $uid, int $amount, string $method): array
    {
        $txId = self::id(); $orderId = self::orderId('AFD'); $now = Time::ms();
        Firebase::db()->runTransaction(function (Transaction $t) use ($uid, $amount, $method, $txId, $orderId, $now) {
            $t->create(self::col('transactions')->document($txId), [
                'userId' => $uid, 'type' => 'deposit', 'method' => $method, 'amount' => $amount, 'fee' => 0, 'total' => $amount,
                'status' => 'pending', 'merchantOrderId' => $orderId, 'platformTxId' => null, 'flowId' => null,
                'errorMsg' => null, 'creditedAmount' => null, 'lastQueryMs' => 0, 'createdAtMs' => $now, 'updatedAtMs' => $now,
            ]);
            $t->create(self::col('payment_orders')->document($orderId), [
                'txId' => $txId, 'userId' => $uid, 'direction' => 'deposit', 'requestedAmount' => $amount, 'createdAtMs' => $now,
            ]);
        });
        return [$txId, $orderId];
    }

    public static function attachPlatformId(string $txId, ?string $platformTxId): void
    {
        if ($platformTxId) {
            self::col('transactions')->document($txId)->update(self::pairs(['platformTxId' => $platformTxId, 'updatedAtMs' => Time::ms()]));
        }
    }

    public static function touchQuery(string $txId): void
    {
        self::col('transactions')->document($txId)->update(self::pairs(['lastQueryMs' => Time::ms()]));
    }

    /** Deposit confirmed by a verified callback or result query. Safe to call any number of times. */
    public static function creditDeposit(string $txId, int $actual, string $platformTxId = ''): array
    {
        $feePct = (float)Settings::get('depositFeePercent');
        $txRef = self::col('transactions')->document($txId);
        $out = ['result' => 'not_found', 'uid' => null, 'net' => 0, 'mismatch' => false];

        Firebase::db()->runTransaction(function (Transaction $t) use ($txId, $txRef, $actual, $platformTxId, $feePct, &$out) {
            $out = ['result' => 'not_found', 'uid' => null, 'net' => 0, 'mismatch' => false];
            $tx = $t->snapshot($txRef);
            if (!$tx->exists()) { return; }
            $d = $tx->data();
            $uid = (string)$d['userId'];
            $lRef = self::col('ledger')->document("dep_credit_{$txId}");
            $wRef = self::col('wallets')->document($uid);
            $l = $t->snapshot($lRef);
            $w = $t->snapshot($wRef);

            if ($d['type'] !== 'deposit' || $actual <= 0) { $out['result'] = 'invalid'; return; }
            if ($l->exists() || $d['status'] === 'successful') { $out['result'] = 'already'; return; }
            if (!$w->exists()) { $out['result'] = 'no_wallet'; return; }

            $fee = Money::percent($actual, $feePct);
            $net = $actual - $fee;
            if ($net <= 0) { $out['result'] = 'invalid'; return; }
            $wd = $w->data();
            $bal = (int)$wd['balance'] + $net;
            $now = Time::ms();

            $t->create($lRef, ['userId' => $uid, 'txId' => $txId, 'kind' => 'deposit_credit', 'amount' => $net,
                               'heldDelta' => 0, 'balanceAfter' => $bal, 'createdAtMs' => $now]);
            $t->update($wRef, self::pairs(['balance' => $bal, 'totalDeposit' => (int)$wd['totalDeposit'] + $net,
                                           'version' => (int)$wd['version'] + 1, 'updatedAtMs' => $now]));
            $t->update($txRef, self::pairs(['status' => 'successful', 'fee' => $fee, 'creditedAmount' => $net, 'actualAmount' => $actual,
                                            'platformTxId' => $platformTxId !== '' ? $platformTxId : ($d['platformTxId'] ?? null),
                                            'errorMsg' => null, 'updatedAtMs' => $now]));
            $out = ['result' => 'credited', 'uid' => $uid, 'net' => $net, 'mismatch' => $actual !== (int)$d['amount']];
        });

        if ($out['mismatch']) {
            AuditLog::log('system', 'deposit.amount_mismatch', "transactions/{$txId}", ['actual' => $actual]);
        }
        return $out;
    }

    /** No money moved: gateway never produced a payment link, or the user abandoned it. */
    public static function cancelDeposit(string $txId, string $reason): void
    {
        $ref = self::col('transactions')->document($txId);
        Firebase::db()->runTransaction(function (Transaction $t) use ($ref, $reason) {
            $s = $t->snapshot($ref);
            if ($s->exists() && in_array($s->data()['status'], ['pending', 'processing'], true)) {
                $t->update($ref, self::pairs(['status' => 'cancelled', 'errorMsg' => $reason, 'updatedAtMs' => Time::ms()]));
            }
        });
    }

    // ------------------------------------------------------------- withdrawals
    /** Moves amount+fee from balance to held and creates the pending transaction. */
    public static function holdWithdraw(string $uid, int $amount, int $fee, string $method, string $walletNo, string $holder): array
    {
        $txId = self::id(); $orderId = self::orderId('AFW'); $total = $amount + $fee;
        $wRef = self::col('wallets')->document($uid);
        $res = 'ok';

        Firebase::db()->runTransaction(function (Transaction $t) use ($uid, $amount, $fee, $total, $method, $walletNo, $holder, $txId, $orderId, $wRef, &$res) {
            $res = 'ok';
            $w = $t->snapshot($wRef);
            if (!$w->exists()) { $res = 'no_wallet'; return; }
            $wd = $w->data();
            if ((int)$wd['balance'] < $total) { $res = 'insufficient'; return; }
            $bal = (int)$wd['balance'] - $total;
            $now = Time::ms();
            $t->create(self::col('ledger')->document("wd_hold_{$txId}"), ['userId' => $uid, 'txId' => $txId, 'kind' => 'withdraw_hold',
                'amount' => -$total, 'heldDelta' => $total, 'balanceAfter' => $bal, 'createdAtMs' => $now]);
            $t->update($wRef, self::pairs(['balance' => $bal, 'held' => (int)$wd['held'] + $total,
                                           'version' => (int)$wd['version'] + 1, 'updatedAtMs' => $now]));
            $t->create(self::col('transactions')->document($txId), [
                'userId' => $uid, 'type' => 'withdraw', 'method' => $method, 'amount' => $amount, 'fee' => $fee, 'total' => $total,
                'status' => 'pending', 'merchantOrderId' => $orderId, 'platformTxId' => null, 'flowId' => null, 'errorMsg' => null,
                'walletNo' => $walletNo, 'holderName' => $holder, 'lastQueryMs' => 0, 'createdAtMs' => $now, 'updatedAtMs' => $now,
            ]);
            $t->create(self::col('payment_orders')->document($orderId), [
                'txId' => $txId, 'userId' => $uid, 'direction' => 'withdraw', 'requestedAmount' => $amount, 'createdAtMs' => $now]);
        });

        if ($res === 'insufficient') { throw new ApiException('INSUFFICIENT_BALANCE', 422); }
        if ($res !== 'ok') { throw new ApiException('SERVICE_UNAVAILABLE', 503); }
        return [$txId, $orderId];
    }

    public static function markWithdrawProcessing(string $txId, ?string $platformTxId): void
    {
        $ref = self::col('transactions')->document($txId);
        Firebase::db()->runTransaction(function (Transaction $t) use ($ref, $platformTxId) {
            $s = $t->snapshot($ref);
            if (!$s->exists()) { return; }
            $f = ['updatedAtMs' => Time::ms()];
            if ($s->data()['status'] === 'pending') { $f['status'] = 'processing'; }
            if ($platformTxId) { $f['platformTxId'] = $platformTxId; }
            $t->update($ref, self::pairs($f));
        });
    }

    /** TashPay confirmed payout: release the hold (money has left). Idempotent. */
    public static function settleWithdraw(string $txId, string $flowId = '', ?int $platformFee = null): array
    {
        $txRef = self::col('transactions')->document($txId);
        $out = ['result' => 'not_found', 'uid' => null, 'amount' => 0];

        Firebase::db()->runTransaction(function (Transaction $t) use ($txId, $txRef, $flowId, $platformFee, &$out) {
            $out = ['result' => 'not_found', 'uid' => null, 'amount' => 0];
            $tx = $t->snapshot($txRef);
            if (!$tx->exists()) { return; }
            $d = $tx->data();
            $uid = (string)$d['userId'];
            $wRef = self::col('wallets')->document($uid);
            $lRef = self::col('ledger')->document("wd_settle_{$txId}");
            $l = $t->snapshot($lRef);
            $w = $t->snapshot($wRef);

            if ($d['type'] !== 'withdraw') { $out['result'] = 'invalid'; return; }
            if ($l->exists() || $d['status'] === 'successful') { $out['result'] = 'already'; return; }
            if ($d['status'] === 'failed' || $d['status'] === 'cancelled') {
                // Payout happened after we refunded. Never auto-debit again; flag for a human.
                $t->update($txRef, self::pairs(['needsReview' => true, 'flowId' => $flowId !== '' ? $flowId : null, 'updatedAtMs' => Time::ms()]));
                $out = ['result' => 'conflict', 'uid' => $uid, 'amount' => (int)$d['amount']];
                return;
            }
            if (!$w->exists()) { $out['result'] = 'no_wallet'; return; }
            $wd = $w->data();
            $total = (int)$d['total'];
            $now = Time::ms();
            $t->create($lRef, ['userId' => $uid, 'txId' => $txId, 'kind' => 'withdraw_settle', 'amount' => 0,
                               'heldDelta' => -$total, 'balanceAfter' => (int)$wd['balance'], 'createdAtMs' => $now]);
            $t->update($wRef, self::pairs(['held' => max(0, (int)$wd['held'] - $total),
                                           'totalWithdraw' => (int)$wd['totalWithdraw'] + (int)$d['amount'],
                                           'version' => (int)$wd['version'] + 1, 'updatedAtMs' => $now]));
            $f = ['status' => 'successful', 'flowId' => $flowId !== '' ? $flowId : null, 'errorMsg' => null, 'updatedAtMs' => $now];
            if ($platformFee !== null) { $f['platformFee'] = $platformFee; }
            $t->update($txRef, self::pairs($f));
            $out = ['result' => 'settled', 'uid' => $uid, 'amount' => (int)$d['amount']];
        });

        if ($out['result'] === 'conflict') {
            AuditLog::log('system', 'withdraw.paid_after_refund', "transactions/{$txId}", ['severity' => 'critical']);
        }
        return $out;
    }

    /** Payout failed / never reached TashPay: return held money to balance. Idempotent. */
    public static function failWithdraw(string $txId, string $reason): array
    {
        $txRef = self::col('transactions')->document($txId);
        $out = ['result' => 'not_found', 'uid' => null, 'total' => 0];

        Firebase::db()->runTransaction(function (Transaction $t) use ($txId, $txRef, $reason, &$out) {
            $out = ['result' => 'not_found', 'uid' => null, 'total' => 0];
            $tx = $t->snapshot($txRef);
            if (!$tx->exists()) { return; }
            $d = $tx->data();
            $uid = (string)$d['userId'];
            $wRef = self::col('wallets')->document($uid);
            $lRef = self::col('ledger')->document("wd_refund_{$txId}");
            $l = $t->snapshot($lRef);
            $w = $t->snapshot($wRef);

            if ($d['type'] !== 'withdraw') { $out['result'] = 'invalid'; return; }
            if (!in_array($d['status'], ['pending', 'processing'], true) || $l->exists()) { $out['result'] = 'already'; return; }
            if (!$w->exists()) { $out['result'] = 'no_wallet'; return; }
            $wd = $w->data();
            $total = (int)$d['total'];
            $bal = (int)$wd['balance'] + $total;
            $now = Time::ms();
            $t->create($lRef, ['userId' => $uid, 'txId' => $txId, 'kind' => 'withdraw_refund', 'amount' => $total,
                               'heldDelta' => -$total, 'balanceAfter' => $bal, 'createdAtMs' => $now]);
            $t->update($wRef, self::pairs(['balance' => $bal, 'held' => max(0, (int)$wd['held'] - $total),
                                           'version' => (int)$wd['version'] + 1, 'updatedAtMs' => $now]));
            $t->update($txRef, self::pairs(['status' => 'failed', 'errorMsg' => mb_substr($reason, 0, 200), 'updatedAtMs' => $now]));
            $out = ['result' => 'refunded', 'uid' => $uid, 'total' => $total];
        });
        return $out;
    }

    // ------------------------------------------------------------ admin adjust
    /** Signed adjustment. Caller must already have checked permission, reason and re-authentication. */
    public static function adminAdjust(string $adminUid, string $uid, int $delta, string $reason): int
    {
        $wRef = self::col('wallets')->document($uid);
        $adjId = 'adj_' . bin2hex(random_bytes(10));
        $res = 'ok'; $newBal = 0;
        Firebase::db()->runTransaction(function (Transaction $t) use ($wRef, $delta, $adjId, $uid, $adminUid, $reason, &$res, &$newBal) {
            $res = 'ok';
            $w = $t->snapshot($wRef);
            if (!$w->exists()) { $res = 'no_wallet'; return; }
            $wd = $w->data();
            $newBal = (int)$wd['balance'] + $delta;
            if ($newBal < 0) { $res = 'negative'; return; }
            $now = Time::ms();
            $t->create(self::col('ledger')->document($adjId), ['userId' => $uid, 'txId' => null, 'kind' => 'admin_adjust',
                'amount' => $delta, 'heldDelta' => 0, 'balanceAfter' => $newBal, 'actor' => $adminUid,
                'reason' => mb_substr($reason, 0, 200), 'createdAtMs' => $now]);
            $t->update($wRef, self::pairs(['balance' => $newBal, 'version' => (int)$wd['version'] + 1, 'updatedAtMs' => $now]));
        });
        if ($res === 'negative') { throw new ApiException('INSUFFICIENT_BALANCE', 422); }
        if ($res !== 'ok') { throw new ApiException('NOT_FOUND', 404); }
        AuditLog::log($adminUid, 'wallet.adjusted', "wallets/{$uid}", ['delta' => $delta, 'reason' => $reason, 'ledger' => $adjId]);
        return $newBal;
    }
}
