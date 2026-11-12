<?php
/** Receipt: server-rendered, printable. $me is the signed-in user (Page::render). */
use App\Core\Money;
use App\Services\AdminGuard;
use App\Services\WalletService;

$txId = is_string($_GET['tx'] ?? null) ? $_GET['tx'] : '';
$tx = preg_match('/^[0-9a-f]{20}$/', $txId) ? WalletService::tx($txId) : null;
$admin = AdminGuard::isAdminSession();
if ($tx === null || ($tx['userId'] !== $me['uid'] && !$admin)) {
    echo '<section class="card"><h2>404</h2><p class="muted">Receipt not found.</p><p><a class="btn btn-primary" href="/history.php">Back to history</a></p></section>';
    return;
}
$t = WalletService::publicTx($tx);
$h = static fn($s) => htmlspecialchars((string)($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$m = static fn($p) => '৳' . Money::fmt((int)$p);
$dt = gmdate('d M Y, h:i A', (int)($t['createdAtMs'] / 1000) + 21600);   // Bangladesh time
$typeLabel = $t['type'] === 'deposit' ? 'Deposit' : ($t['type'] === 'withdraw' ? 'Withdraw' : 'Bonus');
$statusLabel = ucfirst($t['status']);
?>
<a class="back receipt-actions" href="/history.php"><span data-icon="chevron-left"></span><span data-i18n="back"></span></a>
<section class="card receipt-card">
  <div class="receipt-head">
    <div data-logo="64"></div>
    <h2>AF MONEY SAFER</h2>
    <p class="muted" data-i18n="receipt_title"></p>
  </div>
  <div class="receipt-rows">
    <div class="kv"><span class="muted" data-i18n="tx_id"></span><b style="word-break:break-all;text-align:right"><?= $h($t['id']) ?></b></div>
    <?php if ($t['orderId']): ?><div class="kv"><span class="muted" data-i18n="order_id"></span><b style="word-break:break-all;text-align:right"><?= $h($t['orderId']) ?></b></div><?php endif; ?>
    <div class="kv"><span class="muted" data-i18n="type"></span><b><?= $h($typeLabel) ?></b></div>
    <?php if ($t['method']): ?><div class="kv"><span class="muted" data-i18n="method"></span><b><?= $h($t['method']) ?></b></div><?php endif; ?>
    <div class="kv"><span class="muted" data-i18n="amount"></span><b><?= $h($m($t['amount'])) ?></b></div>
    <div class="kv"><span class="muted" data-i18n="fee"></span><b><?= $h($m($t['fee'])) ?></b></div>
    <div class="kv"><span class="muted"><?= $t['type'] === 'deposit' ? 'Credited' : 'Total deducted' ?></span><b><?= $h($m($t['type'] === 'deposit' ? ($t['credited'] ?: $t['amount'] - $t['fee']) : $t['total'])) ?></b></div>
    <?php if ($t['walletNo']): ?><div class="kv"><span class="muted" data-i18n="wallet"></span><b><?= $h($t['walletNo']) ?></b></div><?php endif; ?>
    <?php if ($t['holderName']): ?><div class="kv"><span class="muted" data-i18n="holder"></span><b><?= $h($t['holderName']) ?></b></div><?php endif; ?>
    <div class="kv"><span class="muted" data-i18n="status"></span><span class="pill <?= $h($t['status']) ?>"><?= $h($statusLabel) ?></span></div>
    <div class="kv"><span class="muted" data-i18n="date"></span><b><?= $h($dt) ?></b></div>
    <div class="kv"><span class="muted">Account</span><b><?= $h($me['fullName']) ?> · <?= $h($me['email']) ?></b></div>
  </div>
  <p class="muted receipt-foot" style="text-align:center;font-size:.85rem">AF MONEY SAFER · <?= $h($dt) ?></p>
  <div class="receipt-actions" style="display:flex;gap:10px;margin-top:16px">
    <button class="btn btn-primary btn-block" id="printBtn" type="button"><span data-icon="download"></span><span data-i18n="print_receipt"></span></button>
    <a class="btn btn-ghost btn-block" href="/history.php"><span data-i18n="history"></span></a>
  </div>
</section>
