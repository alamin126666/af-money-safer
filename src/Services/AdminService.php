<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Core\Firebase;
use App\Core\Logger;
use App\Core\Money;
use App\Core\RateLimit;
use App\Core\Settings;
use App\Core\Time;
use App\Core\Validator;

final class AdminService
{
    private const MAX_ADJUST_TAKA = 100000;

    private static function db() { return Firebase::db(); }
    private static function s(array $in, string $k, int $max = 256): string
    {
        $v = $in[$k] ?? ''; return is_string($v) ? mb_substr(trim($v), 0, $max) : '';
    }
    private static function userRow(string $id, array $d): array
    {
        $c = $d['createdAt'] ?? null;
        return ['uid' => $id, 'fullName' => $d['fullName'] ?? '', 'email' => $d['email'] ?? '', 'status' => $d['status'] ?? '',
                'emailVerified' => (bool)($d['emailVerified'] ?? false),
                'createdAtMs' => is_object($c) && method_exists($c, 'get') ? $c->get()->getTimestamp() * 1000 : 0];
    }

    public static function overview(): array
    {
        $users = 0; $disabled = 0;
        foreach (self::db()->collection('users')->select(['status'])->limit(5000)->documents() as $d) {
            $users++; if (($d->data()['status'] ?? '') !== 'active') { $disabled++; }
        }
        $pend = ['deposit' => 0, 'withdraw' => 0];
        foreach (['pending', 'processing'] as $st) {
            foreach (self::db()->collection('transactions')->where('status', '=', $st)->limit(500)->documents() as $d) { $pend[$d->data()['type']]++; }
        }
        $review = 0;
        foreach (self::db()->collection('transactions')->where('needsReview', '=', true)->limit(50)->documents() as $_) { $review++; }
        $awaiting = 0;
        foreach (self::db()->collection('transactions')->where('needsApproval', '=', true)->limit(100)->documents() as $_) { $awaiting++; }
        $totalDep = 0; $totalWd = 0;
        foreach (self::db()->collection('wallets')->select(['totalDeposit', 'totalWithdraw'])->limit(5000)->documents() as $d) {
            $w = $d->data(); $totalDep += (int)($w['totalDeposit'] ?? 0); $totalWd += (int)($w['totalWithdraw'] ?? 0);
        }
        $merchant = null;
        try { $r = TashPay::post('/merchant/balance', []); $merchant = $r['code'] === 0 ? ($r['data']['balance'] ?? null) : null; } catch (\Throwable) {}
        return ['OK', ['users' => $users, 'disabledUsers' => $disabled, 'pendingDeposits' => $pend['deposit'],
                       'pendingWithdraws' => $pend['withdraw'], 'needsReview' => $review, 'awaitingApproval' => $awaiting,
                       'merchantBalance' => $merchant,
                       'totalDeposited' => $totalDep, 'totalWithdrawn' => $totalWd,
                       'maintenance' => (bool)Settings::get('maintenance')]];
    }

    public static function users(array $in): array
    {
        $q = strtolower(self::s($in, 'q', 100));
        $col = self::db()->collection('users');
        $rows = [];
        if ($q !== '') {
            foreach ($col->where('emailLower', '>=', $q)->where('emailLower', '<=', $q . "\u{f8ff}")->limit(30)->documents() as $d) { $rows[] = self::userRow($d->id(), $d->data()); }
        } else {
            foreach ($col->orderBy('createdAt', 'DESC')->limit(30)->documents() as $d) { $rows[] = self::userRow($d->id(), $d->data()); }
        }
        return ['OK', ['items' => $rows]];
    }

    public static function userDetail(array $in): array
    {
        $uid = self::s($in, 'uid', 128);
        $s = self::db()->collection('users')->document($uid)->snapshot();
        if ($uid === '' || !$s->exists()) { throw new ApiException('NOT_FOUND', 404); }
        $tx = [];
        foreach (self::db()->collection('transactions')->where('userId', '=', $uid)->orderBy('createdAtMs', 'DESC')->limit(10)->documents() as $d) {
            $tx[] = self::txRow($d->data() + ['id' => $d->id()]);
        }
        return ['OK', ['user' => self::userRow($uid, $s->data()), 'wallet' => WalletService::wallet($uid), 'transactions' => $tx]];
    }

    public static function setUserStatus(array $admin, array $in): array
    {
        $uid = self::s($in, 'uid', 128); $status = self::s($in, 'status', 10); $reason = self::s($in, 'reason', 200);
        if (!in_array($status, ['active', 'disabled'], true) || strlen($reason) < 5) { throw new ApiException('REASON_REQUIRED', 422); }
        if ($uid === $admin['uid']) { throw new ApiException('FORBIDDEN', 403); }
        $ref = self::db()->collection('users')->document($uid);
        $s = $ref->snapshot();
        if (!$s->exists()) { throw new ApiException('NOT_FOUND', 404); }
        $before = $s->data()['status'] ?? '';
        $ref->update([['path' => 'status', 'value' => $status]]);
        Firebase::auth()->updateUser($uid, ['disabled' => $status === 'disabled']);
        if ($status === 'disabled') { Firebase::auth()->revokeRefreshTokens($uid); AuthService::revokeAllSessions($uid); }
        AuditLog::log($admin['uid'], 'admin.user_status', "users/{$uid}", ['from' => $before, 'to' => $status, 'reason' => $reason]);
        return ['USER_STATUS_UPDATED', ['status' => $status]];
    }

    private static function txRow(array $d): array
    {
        return WalletService::publicTx($d) + ['userId' => $d['userId'], 'platformTxId' => $d['platformTxId'] ?? null,
                'needsReview' => (bool)($d['needsReview'] ?? false), 'errorMsg' => $d['errorMsg'] ?? null];
    }

    public static function transactions(array $in): array
    {
        $col = self::db()->collection('transactions');
        $id = self::s($in, 'id', 60);
        if ($id !== '') {                                           // lookup by tx id or merchant order id
            $tx = WalletService::tx($id);
            if ($tx === null) { foreach ($col->where('merchantOrderId', '=', $id)->limit(1)->documents() as $d) { $tx = $d->data() + ['id' => $d->id()]; } }
            return ['OK', ['items' => $tx ? [self::txRow($tx)] : []]];
        }
        $st = self::s($in, 'status', 12); $ty = self::s($in, 'type', 10);
        $q = $col;
        if ($st === 'awaiting') {
            // Withdraws held for admin approval. Single-field query, sorted in PHP — no new index.
            $rows = [];
            foreach ($q->where('needsApproval', '=', true)->limit(60)->documents() as $d) {
                $r = $d->data() + ['id' => $d->id()];
                if (in_array($ty, ['deposit', 'withdraw'], true) && $r['type'] !== $ty) { continue; }
                $rows[] = self::txRow($r);
            }
            usort($rows, static fn($a, $b) => $b['createdAtMs'] <=> $a['createdAtMs']);
            return ['OK', ['items' => $rows]];
        }
        if (in_array($st, ['pending', 'processing', 'successful', 'failed', 'cancelled'], true)) { $q = $q->where('status', '=', $st); }
        elseif (in_array($ty, ['deposit', 'withdraw'], true)) { $q = $q->where('type', '=', $ty); }
        $rows = [];
        foreach ($q->orderBy('createdAtMs', 'DESC')->limit(60)->documents() as $d) {
            $r = $d->data() + ['id' => $d->id()];
            if (in_array($ty, ['deposit', 'withdraw'], true) && $r['type'] !== $ty) { continue; }
            $rows[] = self::txRow($r);
        }
        return ['OK', ['items' => $rows]];
    }

    public static function txDetail(array $in): array
    {
        $tx = WalletService::tx(self::s($in, 'id', 40));
        if ($tx === null) { throw new ApiException('TX_NOT_FOUND', 404); }
        $ledger = [];
        foreach (self::db()->collection('ledger')->where('txId', '=', $tx['id'])->limit(10)->documents() as $d) { $ledger[] = $d->data() + ['id' => $d->id()]; }
        $o = self::db()->collection('payment_orders')->document((string)$tx['merchantOrderId'])->snapshot();
        return ['OK', ['tx' => self::txRow($tx) + ['walletNoFull' => $tx['walletNo'] ?? null], 'ledger' => $ledger, 'order' => $o->exists() ? $o->data() : null]];
    }

    public static function getSettings(): array { return ['OK', ['settings' => Settings::all()]]; }

    public static function saveSettings(array $admin, array $in): array
    {
        $int = static fn(string $k, int $lo, int $hi) => (isset($in[$k]) && is_numeric($in[$k]) && (int)$in[$k] == $in[$k] && (int)$in[$k] >= $lo && (int)$in[$k] <= $hi)
            ? (int)$in[$k] : throw new ApiException('VALIDATION_FAILED', 422, ['fields' => [$k => 'invalid']]);
        $pct = static fn(string $k) => (isset($in[$k]) && is_numeric($in[$k]) && (float)$in[$k] >= 0 && (float)$in[$k] <= 20)
            ? round((float)$in[$k], 2) : throw new ApiException('VALIDATION_FAILED', 422, ['fields' => [$k => 'invalid']]);
        $maintOn = (bool)($in['maintenance'] ?? false);
        $maintReason = isset($in['maintenanceReason']) && is_string($in['maintenanceReason']) ? mb_substr(trim($in['maintenanceReason']), 0, 200) : '';
        $maintMinutes = (isset($in['maintenanceMinutes']) && is_numeric($in['maintenanceMinutes'])) ? (int)$in['maintenanceMinutes'] : 30;
        if ($maintMinutes < 5 || $maintMinutes > 1440) { $maintMinutes = 30; }
        $new = [
            'minDeposit' => $int('minDeposit', 1, 1000000), 'maxDeposit' => $int('maxDeposit', 1, 1000000),
            'minWithdraw' => $int('minWithdraw', 100, 1000000), 'maxWithdraw' => $int('maxWithdraw', 100, 1000000),   // TashPay minimum 100
            'depositFeePercent' => $pct('depositFeePercent'), 'withdrawFeePercent' => $pct('withdrawFeePercent'),
            'maintenance' => $maintOn,
            'maintenanceReason' => $maintOn ? $maintReason : '',
            'maintenanceUntilMs' => $maintOn ? (time() * 1000 + $maintMinutes * 60000) : 0,
            'withdrawApproval' => (bool)($in['withdrawApproval'] ?? false),
        ];
        if ($new['minDeposit'] > $new['maxDeposit'] || $new['minWithdraw'] > $new['maxWithdraw']) {
            throw new ApiException('VALIDATION_FAILED', 422, ['fields' => ['maxDeposit' => 'min > max']]);
        }
        $before = Settings::all();
        Settings::save($new);
        AuditLog::log($admin['uid'], 'admin.settings_changed', 'settings/app', ['before' => $before, 'after' => $new]);
        return ['SETTINGS_SAVED', ['settings' => $new]];
    }

    // ------------------------------------------------------------ broadcast
    public static function sendBroadcast(array $admin, array $in): array
    {
        RateLimit::hit("broadcast:{$admin['uid']}", 1, 300);   // at most one broadcast per 5 minutes per admin
        $title = self::s($in, 'title', 120);
        $message = self::s($in, 'message', 500);
        if ($title === '' || $message === '') { throw new ApiException('VALIDATION_FAILED', 422); }
        $sent = Notifier::broadcast($title, $message);
        self::db()->collection('broadcasts')->add([
            'title' => $title, 'message' => $message, 'createdBy' => $admin['uid'],
            'sentCount' => $sent, 'createdAtMs' => Time::ms(),
        ]);
        AuditLog::log($admin['uid'], 'admin.broadcast_sent', 'broadcasts', ['sent' => $sent, 'title' => $title]);
        return ['BROADCAST_SENT', ['sent' => $sent]];
    }

    public static function broadcasts(): array
    {
        $rows = [];
        foreach (self::db()->collection('broadcasts')->orderBy('createdAtMs', 'DESC')->limit(20)->documents() as $d) {
            $x = $d->data();
            $rows[] = ['title' => $x['title'] ?? '', 'message' => $x['message'] ?? '',
                       'sentCount' => (int)($x['sentCount'] ?? 0), 'createdAtMs' => (int)($x['createdAtMs'] ?? 0)];
        }
        return ['OK', ['items' => $rows]];
    }

    // -------------------------------------------------------------- reports
    /** ?days=7|30. One bounded scan (<=3000 txs); no new composite index. */
    public static function reports(array $in): array
    {
        $days = (int)($in['days'] ?? 30);
        if (!in_array($days, [7, 30], true)) { $days = 30; }
        $start = Time::ms() - $days * 86400000;
        $totals = ['deposits' => ['count' => 0, 'sum' => 0], 'withdraws' => ['count' => 0, 'sum' => 0], 'bonuses' => ['count' => 0, 'sum' => 0]];
        $perDay = [];
        $methods = ['deposit' => ['bKash' => ['count' => 0, 'sum' => 0], 'Nagad' => ['count' => 0, 'sum' => 0]],
                    'withdraw' => ['bKash' => ['count' => 0, 'sum' => 0], 'Nagad' => ['count' => 0, 'sum' => 0]]];
        $pendingWithdraws = 0;
        foreach (self::db()->collection('transactions')->where('createdAtMs', '>=', $start)->orderBy('createdAtMs', 'DESC')->limit(3000)->documents() as $d) {
            $x = $d->data();
            $day = gmdate('Y-m-d', (int)((int)($x['createdAtMs'] ?? 0) / 1000) + 21600);   // Bangladesh time (UTC+6)
            $perDay[$day] ??= ['deposit' => 0, 'withdraw' => 0];
            $type = $x['type'] ?? '';
            $status = $x['status'] ?? '';
            if ($type === 'deposit' && $status === 'successful') {
                $amt = (int)($x['creditedAmount'] ?? $x['amount']);
                $totals['deposits']['count']++; $totals['deposits']['sum'] += $amt; $perDay[$day]['deposit'] += $amt;
                $m = $x['method'] ?? '';
                if (isset($methods['deposit'][$m])) { $methods['deposit'][$m]['count']++; $methods['deposit'][$m]['sum'] += $amt; }
            } elseif ($type === 'withdraw') {
                if ($status === 'successful') {
                    $amt = (int)$x['amount'];
                    $totals['withdraws']['count']++; $totals['withdraws']['sum'] += $amt; $perDay[$day]['withdraw'] += $amt;
                    $m = $x['method'] ?? '';
                    if (isset($methods['withdraw'][$m])) { $methods['withdraw'][$m]['count']++; $methods['withdraw'][$m]['sum'] += $amt; }
                }
                if ($status === 'pending') { $pendingWithdraws++; }
            } elseif ($type === 'bonus' && $status === 'successful') {
                $totals['bonuses']['count']++; $totals['bonuses']['sum'] += (int)$x['amount'];
            }
        }
        // Fill every day in the range (even empty ones) so the chart has no gaps.
        $series = [];
        $dayMs = 86400000;
        $firstDay = (int)floor(($start + 21600000) / $dayMs);
        $lastDay = (int)floor((Time::ms() + 21600000) / $dayMs);
        for ($d = $firstDay; $d <= $lastDay; $d++) {
            $key = gmdate('Y-m-d', $d * 86400);   // BD date of day $d (d counts days in BD time)
            $series[] = ['date' => $key, 'deposit' => (int)($perDay[$key]['deposit'] ?? 0), 'withdraw' => (int)($perDay[$key]['withdraw'] ?? 0)];
        }
        $newUsers = 0;
        try {
            $ts = new \Google\Cloud\Core\Timestamp(new \DateTime('@' . (int)($start / 1000)));
            foreach (self::db()->collection('users')->where('createdAt', '>=', $ts)->limit(5000)->documents() as $_) { $newUsers++; }
        } catch (\Throwable) { $newUsers = 0; }
        $awaiting = 0;
        foreach (self::db()->collection('transactions')->where('needsApproval', '=', true)->limit(100)->documents() as $_) { $awaiting++; }
        $redemptions = 0;
        foreach (self::db()->collection('promo_redemptions')->where('createdAtMs', '>=', $start)->limit(3000)->documents() as $_) { $redemptions++; }
        return ['OK', ['days' => $days, 'totals' => $totals, 'series' => $series, 'methods' => $methods,
                       'newUsers' => $newUsers, 'pendingWithdraws' => $pendingWithdraws,
                       'awaitingApproval' => $awaiting, 'promoRedemptions' => $redemptions]];
    }

    public static function auditLogs(): array
    {        $rows = [];
        foreach (self::db()->collection('audit_logs')->orderBy('createdAt', 'DESC')->limit(60)->documents() as $d) {
            $x = $d->data(); $c = $x['createdAt'] ?? null;
            $rows[] = ['actor' => $x['actor'], 'action' => $x['action'], 'target' => $x['target'], 'meta' => (object)($x['meta'] ?? []),
                       'createdAtMs' => is_object($c) && method_exists($c, 'get') ? $c->get()->getTimestamp() * 1000 : 0];
        }
        return ['OK', ['items' => $rows]];
    }

    /** Delete every doc in a user-scoped collection. Returns how many were deleted. */
    private static function deleteUserDocs(string $col, string $uid): int
    {
        $n = 0;
        foreach (self::db()->collection($col)->where('userId', '=', $uid)->limit(500)->documents() as $d) {
            $d->reference()->delete(); $n++;
        }
        return $n;
    }

    /**
     * Permanently delete a user account from Firebase Auth AND Firestore.
     * Refuses self-delete and deleting another admin. The admin's own
     * password must be re-entered first. Transactions + ledger are NEVER
     * deleted — they are the financial audit trail.
     */
    public static function deleteUser(array $admin, array $in): array
    {
        $uid = self::s($in, 'uid', 128);
        if ($uid === $admin['uid']) { throw new ApiException('FORBIDDEN', 403); }
        $ref = self::db()->collection('users')->document($uid);
        $s = $ref->snapshot();
        if ($uid === '' || !$s->exists()) { throw new ApiException('NOT_FOUND', 404); }
        if (AdminGuard::adminDoc($uid) !== null) { throw new ApiException('FORBIDDEN', 403); }
        $targetEmail = $s->data()['email'] ?? '';
        AuthService::verifyPassword($admin['uid'], $admin['email'], is_string($in['password'] ?? null) ? $in['password'] : '');
        // Revoke access first, so the account is dead even if a later step fails.
        try { Firebase::auth()->revokeRefreshTokens($uid); } catch (\Throwable) {}
        AuthService::revokeAllSessions($uid);
        // Delete the Auth user; keep going with Firestore cleanup even if the
        // Auth user is already gone (a leftover Auth user with no user doc
        // cannot log in).
        $authDeleted = false;
        try { Firebase::auth()->deleteUser($uid); $authDeleted = true; }
        catch (\Throwable $e) { Logger::error('admin.user_delete_auth', ['uid' => $uid, 'e' => $e->getMessage()]); }
        $ref->delete();
        self::db()->collection('wallets')->document($uid)->delete();
        $n1 = self::deleteUserDocs('saved_numbers', $uid);
        $n2 = self::deleteUserDocs('notifications', $uid);
        $n3 = self::deleteUserDocs('promo_redemptions', $uid);
        AuditLog::log($admin['uid'], 'admin.user_deleted', "users/{$uid}", ['email' => $targetEmail, 'authDeleted' => $authDeleted,
                       'savedNumbers' => $n1, 'notifications' => $n2, 'redemptions' => $n3]);
        return ['USER_DELETED', ['deleted' => ['auth' => $authDeleted, 'user' => true, 'wallet' => true,
                       'savedNumbers' => $n1, 'notifications' => $n2, 'redemptions' => $n3]]];
    }

    /** Balance adjustment: 'balance' permission + reason + admin password re-entry + audit log + ledger entry. */
    public static function adjustBalance(array $admin, array $in): array
    {
        $uid = self::s($in, 'uid', 128); $reason = self::s($in, 'reason', 200);
        $amount = $in['amount'] ?? null;
        if (!is_string($amount) && !is_int($amount)) { throw new ApiException('INVALID_AMOUNT', 422); }
        if (!preg_match('/^-?[1-9]\d{0,8}$/', (string)$amount)) { throw new ApiException('INVALID_AMOUNT', 422); }
        $taka = (int)$amount;
        if (abs($taka) > self::MAX_ADJUST_TAKA) { throw new ApiException('AMOUNT_OUT_OF_RANGE', 422, ['min' => -self::MAX_ADJUST_TAKA, 'max' => self::MAX_ADJUST_TAKA]); }
        if (mb_strlen($reason) < 10) { throw new ApiException('REASON_REQUIRED', 422); }
        if ($uid === $admin['uid']) { throw new ApiException('FORBIDDEN', 403); }
        AuthService::verifyPassword($admin['uid'], $admin['email'], is_string($in['password'] ?? null) ? $in['password'] : '');
        $bal = WalletService::adminAdjust($admin['uid'], $uid, Money::toPoisha($taka), $reason);
        Notifier::push($uid, 'n.balance_adjusted', ['amount' => Money::toPoisha($taka)]);
        return ['BALANCE_ADJUSTED', ['balance' => $bal]];
    }
}
