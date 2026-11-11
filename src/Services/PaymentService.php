<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Core\Config;
use App\Core\Firebase;
use App\Core\Lang;
use App\Core\Logger;
use App\Core\Money;
use App\Core\RateLimit;
use App\Core\Settings;
use App\Core\Time;
use App\Core\Validator;
use App\Mail\Mailer;

final class CallbackRejected extends \RuntimeException {}

final class PaymentService
{
    private const METHODS = ['bKash', 'Nagad'];

    public static function limits(): array
    {
        return [
            'minDeposit'  => (int)Settings::get('minDeposit'),
            'maxDeposit'  => (int)Settings::get('maxDeposit'),
            'minWithdraw' => (int)Settings::get('minWithdraw'),
            'maxWithdraw' => (int)Settings::get('maxWithdraw'),
            'depositFeePercent'  => (float)Settings::get('depositFeePercent'),
            'withdrawFeePercent' => (float)Settings::get('withdrawFeePercent'),
            // Per-method minimums sent to frontend so it can show the right placeholder/hint
            'methodLimits' => [
                'bKash' => [
                    'minDeposit'  => (int)(Config::get('limits.bkash_min_deposit')  ?? Settings::get('minDeposit')),
                    'minWithdraw' => (int)(Config::get('limits.bkash_min_withdraw') ?? Settings::get('minWithdraw')),
                ],
                'Nagad' => [
                    'minDeposit'  => (int)(Config::get('limits.nagad_min_deposit')  ?? Settings::get('minDeposit')),
                    'minWithdraw' => (int)(Config::get('limits.nagad_min_withdraw') ?? Settings::get('minWithdraw')),
                ],
            ],
        ];
    }

    private static function wholeTaka(mixed $v): int
    {
        $s = is_string($v) || is_int($v) ? trim((string)$v) : '';
        if (!preg_match('/^[1-9]\d{0,8}$/', $s)) { throw new ApiException('INVALID_AMOUNT', 422); }
        return (int)$s;
    }

    private static function method(mixed $v): string
    {
        if (!is_string($v) || !in_array($v, self::METHODS, true)) { throw new ApiException('INVALID_METHOD', 422); }
        return $v;
    }

    private static function range(int $amount, int $min, int $max): void
    {
        if ($amount < $min || $amount > $max) {
            throw new ApiException('AMOUNT_OUT_OF_RANGE', 422, ['min' => $min, 'max' => $max]);
        }
    }

    // ================================================================ DEPOSIT
    /** Return the effective min-deposit for a given method (bKash=300, Nagad=100). */
    private static function methodMinDeposit(string $method): int
    {
        $key = strtolower($method) . '_min_deposit';
        $v   = Config::get('limits.' . $key);
        return $v !== null ? (int)$v : (int)Settings::get('minDeposit');
    }

    /** Return the effective min-withdraw for a given method. */
    private static function methodMinWithdraw(string $method): int
    {
        $key = strtolower($method) . '_min_withdraw';
        $v   = Config::get('limits.' . $key);
        return $v !== null ? (int)$v : (int)Settings::get('minWithdraw');
    }

    public static function createDeposit(array $user, array $in): array
    {
        RateLimit::hit("dep_user:{$user['uid']}", 10, 3600);
        $amount = self::wholeTaka($in['amount'] ?? null);
        $method = self::method($in['method'] ?? null);
        $L = self::limits();
        $methodMin = self::methodMinDeposit($method);
        self::range($amount, $methodMin, $L['maxDeposit']);

        [$txId, $orderId] = WalletService::createDeposit($user['uid'], Money::toPoisha($amount), $method);

        try {
            $r = TashPay::post('/repay/link', [
                'orderId' => $orderId, 'amount' => (string)$amount, 'customerName' => $user['fullName'],
                'walletType' => $method, 'notifyUrl' => (string)Config::get('tashpay.deposit_notify_url'),
                'returnUrl' => Config::get('app.url') . '/deposit.php?tx=' . $txId,
            ]);
        } catch (TashPayUnreachable) {
            WalletService::cancelDeposit($txId, 'gateway_unreachable');
            throw new ApiException('GATEWAY_UNAVAILABLE', 503);
        }
        $url = (string)($r['data']['url'] ?? '');
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        if ($r['code'] !== 0 || !in_array($scheme, ['http', 'https'], true)) {
            WalletService::cancelDeposit($txId, 'gateway_error_' . $r['code']);
            throw new ApiException('GATEWAY_ERROR', 502);
        }
        WalletService::attachPlatformId($txId, isset($r['data']['transactionId']) ? (string)$r['data']['transactionId'] : null);
        return ['DEPOSIT_CREATED', ['txId' => $txId, 'url' => $url]];
    }

    // ================================================================ WITHDRAW
    private static function normalizeWallet(mixed $v): string
    {
        $s = is_string($v) ? preg_replace('/[\s\-]/', '', $v) : '';
        if (!is_string($s) || !preg_match('/^(?:\+?88)?(01[3-9]\d{8})$/', $s, $m)) { throw new ApiException('INVALID_WALLET', 422); }
        return $m[1];
    }

    private static function fee(int $amountTaka): int
    {
        return Money::percent(Money::toPoisha($amountTaka), (float)Settings::get('withdrawFeePercent'));
    }

    /** Step 1: validate the form, check balance, e-mail an OTP, keep the details server-side in the session. */
    public static function requestWithdrawOtp(array $user, array $in): array
    {
        RateLimit::hit("wd_req_user:{$user['uid']}", 8, 3600);
        $method = self::method($in['method'] ?? null);
        $amount = self::wholeTaka($in['amount'] ?? null);
        $L = self::limits();
        $methodMin = self::methodMinWithdraw($method);
        self::range($amount, $methodMin, $L['maxWithdraw']);
        $wallet = self::normalizeWallet($in['walletNo'] ?? '');
        $holder = Validator::name(is_string($in['holderName'] ?? null) ? $in['holderName'] : '');
        Validator::failIf($holder === null ? ['holderName' => 'FIELD_NAME_INVALID'] : []);

        $fee = self::fee($amount); $total = Money::toPoisha($amount) + $fee;
        if (WalletService::wallet($user['uid'])['balance'] < $total) { throw new ApiException('INSUFFICIENT_BALANCE', 422); }

        $prev = $_SESSION['wd']['otpId'] ?? null;
        if (isset($_SESSION['wd']['sentAt']) && time() - (int)$_SESSION['wd']['sentAt'] < (int)Config::get('otp.resend_after')) {
            throw new ApiException('OTP_COOLDOWN', 429, ['retryAfter' => (int)Config::get('otp.resend_after') - (time() - (int)$_SESSION['wd']['sentAt'])]);
        }
        $otp = OtpService::issue('withdraw', $user['email'], $user['uid'], is_string($prev) ? $prev : null);
        $_SESSION['wd'] = ['otpId' => $otp['id'], 'amount' => $amount, 'method' => $method, 'walletNo' => $wallet,
                           'holder' => $holder, 'sentAt' => time()];
        if (!Mailer::sendOtp($user['email'], 'withdraw', $otp['code'], $otp['expiresIn'], Lang::current())) {
            throw new ApiException('MAIL_FAILED', 502);
        }
        return ['WITHDRAW_OTP_SENT', [
            'email' => AuthService::maskEmail($user['email']), 'expiresIn' => $otp['expiresIn'],
            'resendIn' => (int)Config::get('otp.resend_after'), 'amount' => Money::toPoisha($amount), 'fee' => $fee, 'total' => $total,
        ]];
    }

    /** Step 2: OTP ok -> hold balance -> call TashPay. */
    public static function createWithdraw(array $user, array $in): array
    {
        $w = $_SESSION['wd'] ?? null;
        if (!is_array($w)) { throw new ApiException('NO_PENDING', 400); }
        RateLimit::hit("wd_user:{$user['uid']}", 6, 3600);
        $code = preg_replace('/\D/', '', is_string($in['code'] ?? null) ? $in['code'] : '') ?? '';
        $r = OtpService::verify((string)$w['otpId'], 'withdraw', $code);
        if ($r['status'] !== 'ok') { throw new ApiException($r['status'], 400, ['attemptsLeft' => $r['attemptsLeft']]); }
        unset($_SESSION['wd']);                                    // the OTP and the stored details are single-use

        $amount = (int)$w['amount'];
        $L = self::limits();
        self::range($amount, $L['minWithdraw'], $L['maxWithdraw']);
        $fee = self::fee($amount);

        [$txId] = WalletService::holdWithdraw($user['uid'], Money::toPoisha($amount), $fee, (string)$w['method'], (string)$w['walletNo'], (string)$w['holder']);
        $tx = WalletService::tx($txId);

        try {
            $res = TashPay::post('/withdraw', [
                'orderId' => $tx['merchantOrderId'], 'amount' => (string)$amount, 'walletType' => $w['method'],
                'receiveUsername' => $w['holder'], 'receiveWalletNo' => $w['walletNo'],
                'notifyUrl' => (string)Config::get('tashpay.withdraw_notify_url'),
            ]);
        } catch (TashPayUnreachable $e) {
            // Outcome unknown: the money may or may not be on its way. Keep it held; reconcile asks TashPay later.
            Logger::warn('withdraw.unknown_outcome', ['tx' => $txId]);
            Notifier::push($user['uid'], 'n.withdraw_submitted', ['amount' => Money::toPoisha($amount)]);
            return ['WITHDRAW_SUBMITTED', ['txId' => $txId, 'status' => 'pending']];
        }

        if ($res['code'] === 0) {
            WalletService::markWithdrawProcessing($txId, isset($res['data']['transactionId']) ? (string)$res['data']['transactionId'] : null);
            Notifier::push($user['uid'], 'n.withdraw_submitted', ['amount' => Money::toPoisha($amount)]);
            return ['WITHDRAW_SUBMITTED', ['txId' => $txId, 'status' => 'processing']];
        }
        if (in_array($res['code'], TashPay::WITHDRAW_REJECTED, true)) {
            WalletService::failWithdraw($txId, 'gateway_rejected_' . $res['code']);
            AuditLog::log('system', 'withdraw.gateway_rejected', "transactions/{$txId}", ['code' => $res['code'], 'msg' => $res['msg']]);
            throw new ApiException('WITHDRAW_REJECTED', 502, ['txId' => $txId]);
        }
        Logger::warn('withdraw.ambiguous_code', ['tx' => $txId, 'code' => $res['code']]);   // e.g. 5007 / 500: reconcile decides
        return ['WITHDRAW_SUBMITTED', ['txId' => $txId, 'status' => 'pending']];
    }

    // ================================================================ STATUS (owner)
    public static function status(array $user, string $txId, string $type): array
    {
        $tx = preg_match('/^[0-9a-f]{20}$/', $txId) ? WalletService::tx($txId) : null;
        if ($tx === null || $tx['userId'] !== $user['uid'] || $tx['type'] !== $type) { throw new ApiException('TX_NOT_FOUND', 404); }
        if (in_array($tx['status'], ['pending', 'processing'], true) && Time::ms() - (int)($tx['lastQueryMs'] ?? 0) > 8000) {
            ReconcileService::check($tx);
            $tx = WalletService::tx($txId) ?? $tx;
        }
        return ['OK', ['tx' => WalletService::publicTx($tx), 'wallet' => WalletService::wallet($user['uid'])]];
    }

    // ================================================================ CALLBACKS
    private static function eventKey(string $dir, array $p): string
    {
        return hash('sha256', implode('|', [$dir, $p['orderId'] ?? '', $p['transactionId'] ?? '', $p['status'] ?? '', $p['amount'] ?? '']));
    }

    private static function eventDone(string $key): bool
    {
        $s = Firebase::db()->collection('webhook_events')->document($key)->snapshot();
        return $s->exists() && !empty($s->data()['processedAtMs']);
    }

    private static function eventStore(string $key, string $dir, array $p, bool $done): void
    {
        Firebase::db()->collection('webhook_events')->document($key)->set([
            'direction' => $dir, 'orderId' => $p['orderId'] ?? null, 'status' => $p['status'] ?? null, 'amount' => $p['amount'] ?? null,
            'receivedAtMs' => Time::ms(), 'processedAtMs' => $done ? Time::ms() : null,
        ], ['merge' => true]);
    }

    private static function order(array $p, string $direction): array
    {
        $oid = (string)($p['orderId'] ?? '');
        if ($oid === '' || strlen($oid) > 50) { throw new CallbackRejected('bad orderId'); }
        $s = Firebase::db()->collection('payment_orders')->document($oid)->snapshot();
        if (!$s->exists() || $s->data()['direction'] !== $direction) { throw new CallbackRejected('unknown order'); }
        return $s->data();
    }

    /** @return string body to send back ("SUCCESS") @throws CallbackRejected for invalid callbacks */
    public static function handleDepositCallback(array $p): string
    {
        if (!TashPay::verifyCallback($p)) { throw new CallbackRejected('bad signature/account'); }
        $order = self::order($p, 'deposit');
        $key = self::eventKey('dep', $p);
        if (self::eventDone($key)) { return 'SUCCESS'; }
        self::eventStore($key, 'deposit', $p, false);

        if ((string)($p['status'] ?? '') === '1') {
            $actual = Money::parseDecimal((string)($p['amount'] ?? ''));
            if ($actual === null || $actual <= 0) { throw new CallbackRejected('bad amount'); }
            $r = WalletService::creditDeposit((string)$order['txId'], $actual, (string)($p['transactionId'] ?? ''));
            if ($r['result'] === 'credited') { self::afterDeposit((string)$r['uid'], (int)$r['net']); }
            elseif (!in_array($r['result'], ['already'], true)) { throw new \RuntimeException('credit failed: ' . $r['result']); }
        }
        self::eventStore($key, 'deposit', $p, true);
        return 'SUCCESS';
    }

    public static function handleWithdrawCallback(array $p): string
    {
        if (!TashPay::verifyCallback($p)) { throw new CallbackRejected('bad signature/account'); }
        $order = self::order($p, 'withdraw');
        $key = self::eventKey('wd', $p);
        if (self::eventDone($key)) { return 'SUCCESS'; }
        self::eventStore($key, 'withdraw', $p, false);

        $status = (string)($p['status'] ?? '');
        if ($status === '1') {
            $fee = Money::parseDecimal((string)($p['fee'] ?? ''));
            $r = WalletService::settleWithdraw((string)$order['txId'], (string)($p['flowId'] ?? ''), $fee);
            if ($r['result'] === 'settled') { self::afterWithdraw((string)$r['uid'], 'success', (int)$r['amount']); }
            elseif (!in_array($r['result'], ['already', 'conflict'], true)) { throw new \RuntimeException('settle failed: ' . $r['result']); }
        } elseif ($status === '2') {
            $r = WalletService::failWithdraw((string)$order['txId'], (string)($p['errorMsg'] ?? 'failed'));
            if ($r['result'] === 'refunded') { self::afterWithdraw((string)$r['uid'], 'failed', (int)$r['total']); }
            elseif ($r['result'] !== 'already') { throw new \RuntimeException('refund failed: ' . $r['result']); }
        }
        self::eventStore($key, 'withdraw', $p, true);
        return 'SUCCESS';
    }

    // ================================================================ side effects (best effort)
    public static function afterDeposit(string $uid, int $net): void
    {
        Notifier::push($uid, 'n.deposit_success', ['amount' => $net]);
        self::mailUser($uid, 'deposit_success', $net);
    }

    public static function afterWithdraw(string $uid, string $result, int $amount): void
    {
        Notifier::push($uid, $result === 'success' ? 'n.withdraw_success' : 'n.withdraw_failed', ['amount' => $amount]);
        self::mailUser($uid, $result === 'success' ? 'withdraw_success' : 'withdraw_failed', $amount);
    }

    private static function mailUser(string $uid, string $event, int $amount): void
    {
        try {
            $s = Firebase::db()->collection('users')->document($uid)->snapshot();
            if ($s->exists()) {
                $u = $s->data();
                Mailer::sendTxNotice((string)$u['email'], $event, Money::fmt($amount), in_array($u['lang'] ?? '', ['bn', 'en'], true) ? $u['lang'] : 'bn');
            }
        } catch (\Throwable $e) {
            Logger::error('tx.mail_failed', ['e' => $e->getMessage()]);
        }
    }
}
