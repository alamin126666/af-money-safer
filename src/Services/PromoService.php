<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Core\Firebase;
use App\Core\Money;
use App\Core\RateLimit;
use App\Core\Time;
use Google\Cloud\Firestore\FieldValue;
use Google\Cloud\Firestore\Transaction;

/**
 * Promo / bonus codes. A code is a document in `promo_codes` (doc id = the
 * code itself); one redemption per user is tracked in `promo_redemptions`
 * (doc id = code_uid). Redeeming credits the bonus in ONE transaction that
 * follows the WalletService ledger pattern (deterministic ledger id, wallet
 * + transaction + redemption written together), and creates a `bonus`
 * transaction so it shows in history. Bonuses never touch
 * totalDeposit/totalWithdraw.
 */
final class PromoService
{
    private static function col() { return Firebase::db()->collection('promo_codes'); }

    private static function code(mixed $v): string
    {
        $s = is_string($v) ? strtoupper(trim($v)) : '';
        /* Codes are uppercase letters/digits, hyphens allowed inside
           (e.g. AF-3838-BBB4 from the admin generator): 4-20 chars, no
           leading/trailing hyphen, no double hyphen. */
        if (strlen($s) < 4 || strlen($s) > 20 || !preg_match('/^[A-Z0-9-]+$/', $s)
            || $s[0] === '-' || substr($s, -1) === '-' || str_contains($s, '--')) {
            throw new ApiException('PROMO_INVALID_CODE', 422);
        }
        return $s;
    }

    private static function row(string $id, array $d): array
    {
        return ['code' => $id, 'amount' => (int)($d['amount'] ?? 0), 'maxUses' => (int)($d['maxUses'] ?? 0),
                'usedCount' => (int)($d['usedCount'] ?? 0), 'expiresAtMs' => (int)($d['expiresAtMs'] ?? 0),
                'active' => (bool)($d['active'] ?? false), 'createdBy' => $d['createdBy'] ?? null,
                'createdAtMs' => (int)($d['createdAtMs'] ?? 0)];
    }

    // ------------------------------------------------------------------ admin
    public static function list(): array
    {
        $rows = [];
        foreach (self::col()->limit(200)->documents() as $d) { $rows[] = self::row($d->id(), $d->data()); }
        usort($rows, static fn($a, $b) => $b['createdAtMs'] <=> $a['createdAtMs']);
        return ['OK', ['items' => $rows]];
    }

    public static function create(array $admin, array $in): array
    {
        $code = self::code($in['code'] ?? null);
        $amountTaka = $in['amount'] ?? null;
        if ((!is_string($amountTaka) && !is_int($amountTaka)) || !preg_match('/^[1-9]\d{0,5}$/', (string)$amountTaka)) {
            throw new ApiException('INVALID_AMOUNT', 422);
        }
        $amount = Money::toPoisha((int)$amountTaka);
        if ((int)$amountTaka < 10 || (int)$amountTaka > 100000) { throw new ApiException('AMOUNT_OUT_OF_RANGE', 422, ['min' => 10, 'max' => 100000]); }
        $maxUses = isset($in['maxUses']) && is_numeric($in['maxUses']) ? (int)$in['maxUses'] : 0;
        if ($maxUses < 0 || $maxUses > 1000000) { throw new ApiException('VALIDATION_FAILED', 422, ['fields' => ['maxUses' => 'invalid']]); }
        $days = isset($in['expiryDays']) && is_numeric($in['expiryDays']) ? (int)$in['expiryDays'] : 0;
        if ($days < 0 || $days > 3650) { throw new ApiException('VALIDATION_FAILED', 422, ['fields' => ['expiryDays' => 'invalid']]); }
        $ref = self::col()->document($code);
        if ($ref->snapshot()->exists()) { throw new ApiException('PROMO_EXISTS', 409); }
        $ref->set([
            'amount' => $amount, 'maxUses' => $maxUses, 'usedCount' => 0,
            'expiresAtMs' => $days > 0 ? Time::ms() + $days * 86400000 : 0,
            'active' => (bool)($in['active'] ?? true), 'createdBy' => $admin['uid'], 'createdAtMs' => Time::ms(),
        ]);
        AuditLog::log($admin['uid'], 'admin.promo_created', "promo_codes/{$code}", ['amount' => $amount]);
        return ['PROMO_CREATED', ['code' => $code]];
    }

    public static function toggle(array $admin, array $in): array
    {
        $code = self::code($in['code'] ?? null);
        $ref = self::col()->document($code);
        $s = $ref->snapshot();
        if (!$s->exists()) { throw new ApiException('NOT_FOUND', 404); }
        $active = !empty($s->data()['active']) ? false : true;
        if (isset($in['active'])) { $active = (bool)$in['active']; }
        $ref->update([['path' => 'active', 'value' => $active]]);
        AuditLog::log($admin['uid'], 'admin.promo_toggled', "promo_codes/{$code}", ['active' => $active]);
        return ['PROMO_UPDATED', ['code' => $code, 'active' => $active]];
    }

    public static function delete(array $admin, array $in): array
    {
        $code = self::code($in['code'] ?? null);
        $ref = self::col()->document($code);
        if (!$ref->snapshot()->exists()) { throw new ApiException('NOT_FOUND', 404); }
        $ref->delete();
        AuditLog::log($admin['uid'], 'admin.promo_deleted', "promo_codes/{$code}");
        return ['PROMO_DELETED', ['code' => $code]];
    }

    // ------------------------------------------------------------------- user
    /** The signed-in user's own redemptions, newest first. Sorted in PHP:
       promo_redemptions has no userId+createdAtMs composite index, and the
       per-user list is small. */
    public static function redemptions(array $user): array
    {
        $rows = [];
        foreach (Firebase::db()->collection('promo_redemptions')->where('userId', '=', $user['uid'])->limit(100)->documents() as $d) {
            $x = $d->data();
            $rows[] = ['code' => (string)($x['code'] ?? ''), 'amount' => (int)($x['amount'] ?? 0),
                       'createdAtMs' => (int)($x['createdAtMs'] ?? 0)];
        }
        usort($rows, static fn($a, $b) => $b['createdAtMs'] <=> $a['createdAtMs']);
        return ['OK', ['items' => array_slice($rows, 0, 50)]];
    }

    public static function redeem(array $user, array $in): array
    {
        RateLimit::hit("promo_redeem:{$user['uid']}", 10, 3600);
        $code = self::code($in['code'] ?? null);
        $now = Time::ms();
        $out = ['result' => 'not_found', 'amount' => 0];

        Firebase::db()->runTransaction(function (Transaction $t) use ($user, $code, $now, &$out) {
            $out = ['result' => 'not_found', 'amount' => 0];
            $pRef = self::col()->document($code);
            $p = $t->snapshot($pRef);
            if (!$p->exists()) { return; }
            $d = $p->data();
            if (empty($d['active'])) { $out['result'] = 'inactive'; return; }
            if ((int)($d['expiresAtMs'] ?? 0) > 0 && (int)$d['expiresAtMs'] <= $now) { $out['result'] = 'expired'; return; }
            $maxUses = (int)($d['maxUses'] ?? 0);
            if ($maxUses > 0 && (int)($d['usedCount'] ?? 0) >= $maxUses) { $out['result'] = 'used_up'; return; }
            $rRef = Firebase::db()->collection('promo_redemptions')->document($code . '_' . $user['uid']);
            if ($t->snapshot($rRef)->exists()) { $out['result'] = 'already'; return; }

            $amount = (int)$d['amount'];
            $wRef = Firebase::db()->collection('wallets')->document($user['uid']);
            $w = $t->snapshot($wRef);
            if (!$w->exists()) { $out['result'] = 'no_wallet'; return; }
            $wd = $w->data();
            $bal = (int)$wd['balance'] + $amount;
            $txId = WalletService::id();
            $lRef = Firebase::db()->collection('ledger')->document("bonus_{$code}_{$user['uid']}");
            if ($t->snapshot($lRef)->exists()) { $out['result'] = 'already'; return; }

            $t->create($lRef, ['userId' => $user['uid'], 'txId' => $txId, 'kind' => 'bonus_credit', 'amount' => $amount,
                              'heldDelta' => 0, 'balanceAfter' => $bal, 'createdAtMs' => $now]);
            $t->update($wRef, [['path' => 'balance', 'value' => $bal], ['path' => 'version', 'value' => (int)$wd['version'] + 1],
                                ['path' => 'updatedAtMs', 'value' => $now]]);
            $t->create(Firebase::db()->collection('transactions')->document($txId), [
                'userId' => $user['uid'], 'type' => 'bonus', 'method' => null, 'amount' => $amount, 'fee' => 0, 'total' => $amount,
                'status' => 'successful', 'merchantOrderId' => null, 'platformTxId' => null, 'flowId' => null,
                'errorMsg' => null, 'creditedAmount' => $amount, 'promoCode' => $code,
                'walletNo' => null, 'holderName' => null, 'lastQueryMs' => 0, 'createdAtMs' => $now, 'updatedAtMs' => $now,
            ]);
            $t->create($rRef, ['code' => $code, 'userId' => $user['uid'], 'amount' => $amount, 'createdAtMs' => $now]);
            $t->update($pRef, [['path' => 'usedCount', 'value' => FieldValue::increment(1)]]);
            $out = ['result' => 'ok', 'amount' => $amount, 'balance' => $bal];
        });

        if ($out['result'] === 'inactive') { throw new ApiException('PROMO_INACTIVE', 422); }
        if ($out['result'] === 'expired') { throw new ApiException('PROMO_EXPIRED', 422); }
        if ($out['result'] === 'used_up') { throw new ApiException('PROMO_USED_UP', 422); }
        if ($out['result'] === 'already') { throw new ApiException('PROMO_ALREADY_REDEEMED', 409); }
        if ($out['result'] !== 'ok') { throw new ApiException('PROMO_INVALID_CODE', 422); }
        Notifier::push($user['uid'], 'n.promo_redeemed', ['amount' => (int)$out['amount']]);
        return ['PROMO_REDEEMED', ['amount' => (int)$out['amount'], 'balance' => (int)$out['balance']]];
    }
}
