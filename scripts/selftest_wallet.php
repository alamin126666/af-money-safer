<?php
declare(strict_types=1);
// Offline test of the money logic against an in-memory Firestore double (run after `composer install`).
namespace Google\Cloud\Firestore { if (!class_exists(Transaction::class)) { class Transaction {} } }

namespace Tests {
    use Google\Cloud\Firestore\Transaction;

    final class Snap { public function __construct(private string $id, private ?array $d) {}
        public function exists(): bool { return $this->d !== null; } public function data(): array { return $this->d ?? []; } public function id(): string { return $this->id; } }
    final class Ref { public function __construct(private Db $db, public string $path) {}
        public function snapshot(): Snap { return new Snap(basename($this->path), $this->db->store[$this->path] ?? null); }
        public function set(array $d, array $o = []): void { $this->db->store[$this->path] = !empty($o['merge']) ? array_replace($this->db->store[$this->path] ?? [], $d) : $d; }
        public function update(array $pairs): void { foreach ($pairs as $p) { $this->db->store[$this->path][$p['path']] = $p['value']; } }
        public function delete(): void { unset($this->db->store[$this->path]); } }
    final class Col { public function __construct(private Db $db, private string $n) {}
        public function document(string $id): Ref { return new Ref($this->db, "{$this->n}/{$id}"); }
        public function add(array $d): void { $this->db->store["{$this->n}/" . bin2hex(random_bytes(6))] = $d; } }
    final class Tx extends Transaction { public array $ops = []; public function __construct(private Db $db) {}
        public function snapshot(Ref $r): Snap { return $r->snapshot(); }
        public function create(Ref $r, array $d): void { $this->ops[] = ['c', $r, $d]; }
        public function set(Ref $r, array $d): void { $this->ops[] = ['s', $r, $d]; }
        public function update(Ref $r, array $p): void { $this->ops[] = ['u', $r, $p]; } }
    final class Db { public array $store = [];
        public function collection(string $n): Col { return new Col($this, $n); }
        public function runTransaction(callable $f): void {
            $t = new Tx($this); $f($t); $seen = [];
            foreach ($t->ops as [$k, $r, $d]) { if ($k === 'c') { if (isset($this->store[$r->path]) || isset($seen[$r->path])) { throw new \RuntimeException('ALREADY_EXISTS'); } $seen[$r->path] = 1; } }
            foreach ($t->ops as [$k, $r, $d]) { $k === 'u' ? $r->update($d) : $r->set($d); }
        } }
}

namespace {
    $_ENV['APP_SECRET'] = str_repeat('a', 64); $_ENV['TASHPAY_ACCOUNT'] = 'acc1'; $_ENV['TASHPAY_API_KEY'] = 'secretkey';
    require dirname(__DIR__) . '/vendor/autoload.php';
    use App\Core\{Firebase, Settings, Money, ApiException};
    use App\Services\{TashPay, WalletService, PaymentService, CallbackRejected};

    $fail = 0;
    function t(string $n, bool $ok): void { global $fail; echo ($ok ? 'PASS ' : 'FAIL ') . $n . "\n"; if (!$ok) { $fail++; } }
    function sign(array $p): array { $p['sign'] = TashPay::sign($p, 'secretkey'); return $p; }

    // ---- signature
    t('sign rule', TashPay::sign(['b' => '2', 'a' => '1', 'sign' => 'zz', 'c' => '  ', 'd' => null, 'e' => ['x'], 'f' => 5], 'K') === md5('a=1&b=2&f=5&key=K'));
    $cb = sign(['account' => 'acc1', 'orderId' => 'O1', 'status' => 1, 'amount' => '100.00']);
    t('callback ok', TashPay::verifyCallback($cb));
    t('callback tampered', !TashPay::verifyCallback(['amount' => '999.00'] + $cb));
    t('callback wrong account', !TashPay::verifyCallback(sign(['account' => 'evil', 'orderId' => 'O1', 'status' => 1])));
    t('callback no sign', !TashPay::verifyCallback(['account' => 'acc1']));

    // ---- money
    t('parseDecimal', Money::parseDecimal('100.00') === 10000 && Money::parseDecimal('7.5') === 750 && Money::parseDecimal('1e3') === null && Money::parseDecimal('-5') === null);
    t('fmt/fee', Money::fmt(123456) === '1,234.56' && Money::percent(10000, 1.5) === 150 && Money::percent(333, 1) === 4);

    // ---- wallet
    $db = new Tests\Db(); Firebase::useDb($db);
    Settings::useArray(['depositFeePercent' => 0.0, 'withdrawFeePercent' => 0.0, 'maintenance' => false]);
    $db->store['wallets/u1'] = ['balance' => 0, 'held' => 0, 'totalDeposit' => 0, 'totalWithdraw' => 0, 'version' => 0];
    $w = fn() => WalletService::wallet('u1');
    $ledger = fn() => count(array_filter(array_keys($db->store), fn($k) => str_starts_with($k, 'ledger/')));

    [$tx1, $o1] = WalletService::createDeposit('u1', 10000, 'bKash');
    $pay = sign(['account' => 'acc1', 'orderId' => $o1, 'transactionId' => 'T1', 'status' => 1, 'amount' => '100.00']);
    t('deposit callback', PaymentService::handleDepositCallback($pay) === 'SUCCESS' && $w()['balance'] === 10000 && $w()['totalDeposit'] === 10000);
    t('same callback again', PaymentService::handleDepositCallback($pay) === 'SUCCESS' && $w()['balance'] === 10000);
    $pay2 = sign(['account' => 'acc1', 'orderId' => $o1, 'transactionId' => 'T1b', 'status' => 1, 'amount' => '100.00']);
    t('retry w/ other event key still no double credit', PaymentService::handleDepositCallback($pay2) === 'SUCCESS' && $w()['balance'] === 10000 && $ledger() === 1);
    t('tx successful', WalletService::tx($tx1)['status'] === 'successful');
    try { PaymentService::handleDepositCallback(['amount' => '9999.00'] + $pay); t('tampered rejected', false); } catch (CallbackRejected) { t('tampered rejected', $w()['balance'] === 10000); }
    try { PaymentService::handleDepositCallback(sign(['account' => 'acc1', 'orderId' => 'NOPE', 'status' => 1, 'amount' => '5.00'])); t('unknown order rejected', false); } catch (CallbackRejected) { t('unknown order rejected', true); }
    $r = WalletService::creditDeposit($tx1, 10000); t('direct credit repeat = already', $r['result'] === 'already' && $w()['balance'] === 10000);
    [$tx1b, $o1b] = WalletService::createDeposit('u1', 5000, 'Nagad');
    PaymentService::handleDepositCallback(sign(['account' => 'acc1', 'orderId' => $o1b, 'transactionId' => 'T2', 'status' => 0, 'amount' => '50.00']));
    t('status!=1 does not credit', $w()['balance'] === 10000 && WalletService::tx($tx1b)['status'] === 'pending');

    // ---- withdraw
    $db->store['wallets/u1']['balance'] = 50000;
    [$wd1, $wo1] = WalletService::holdWithdraw('u1', 20000, 200, 'bKash', '01712345678', 'Rahim');
    t('hold', $w()['balance'] === 29800 && $w()['held'] === 20200);
    try { WalletService::holdWithdraw('u1', 100000, 0, 'bKash', '01712345678', 'Rahim'); t('insufficient blocked', false); } catch (ApiException $e) { t('insufficient blocked', $e->errorCode === 'INSUFFICIENT_BALANCE' && $w()['balance'] === 29800); }
    $wcb = sign(['account' => 'acc1', 'orderId' => $wo1, 'transactionId' => 'W1', 'flowId' => 'F1', 'status' => 1, 'amount' => '200.00', 'fee' => '2.00']);
    t('withdraw success callback', PaymentService::handleWithdrawCallback($wcb) === 'SUCCESS' && $w()['held'] === 0 && $w()['totalWithdraw'] === 20000 && $w()['balance'] === 29800);
    t('withdraw callback repeat', PaymentService::handleWithdrawCallback($wcb) === 'SUCCESS' && $w()['totalWithdraw'] === 20000);
    t('fail after success ignored', WalletService::failWithdraw($wd1, 'x')['result'] === 'already' && $w()['balance'] === 29800);

    [$wd2, $wo2] = WalletService::holdWithdraw('u1', 10000, 0, 'Nagad', '01812345678', 'Karim');
    t('hold 2', $w()['balance'] === 19800 && $w()['held'] === 10000);
    $fcb = sign(['account' => 'acc1', 'orderId' => $wo2, 'transactionId' => 'W2', 'status' => 2, 'errorMsg' => 'wallet invalid']);
    PaymentService::handleWithdrawCallback($fcb);
    t('failed withdraw refunds', $w()['balance'] === 29800 && $w()['held'] === 0 && WalletService::tx($wd2)['status'] === 'failed');
    PaymentService::handleWithdrawCallback($fcb); WalletService::failWithdraw($wd2, 'again');
    t('refund only once', $w()['balance'] === 29800);
    $late = WalletService::settleWithdraw($wd2, 'F9');
    t('paid-after-refund flagged, no auto debit', $late['result'] === 'conflict' && $w()['balance'] === 29800 && WalletService::tx($wd2)['needsReview'] === true);

    // ---- admin adjust
    t('adjust +', WalletService::adminAdjust('adm', 'u1', 5000, 'test reason!') === 34800);
    try { WalletService::adminAdjust('adm', 'u1', -99999999, 'too much'); t('adjust cannot go negative', false); } catch (ApiException) { t('adjust cannot go negative', $w()['balance'] === 34800); }
    // Ledger invariant: balance was seeded to 50000; every later change must be explained by ledger entries.
    $sum = 0; foreach ($db->store as $k => $v) { if (str_starts_with($k, 'ledger/') && !str_starts_with($k, 'ledger/dep_credit_')) { $sum += $v['amount']; } }
    t('ledger explains balance (50000 + ledger = balance, nothing held)', $w()['balance'] === 50000 + $sum && $w()['held'] === 0);
    $heldSum = 0; foreach ($db->store as $k => $v) { if (str_starts_with($k, 'ledger/')) { $heldSum += $v['heldDelta']; } }
    t('ledger heldDelta sums to wallet.held', $heldSum === $w()['held']);
    echo $fail ? "\n$fail FAILED\n" : "\nALL PASSED\n"; exit($fail ? 1 : 0);
}
