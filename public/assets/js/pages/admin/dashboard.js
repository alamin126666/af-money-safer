/* Admin · Dashboard: live stats, fees, system status, recent transactions. */
AF.ready(function () {
  var $ = AF.$, A = window.AFAdmin, E = A.E, tk = A.tk, dt = A.dt, st = A.st;
  AF.api('admin/overview.php', undefined, { quiet: true }).then(function (j) {
    if (!j.success) { return; } var d = j.data;
    var cards = [
      ['user', 't-green', 'Users', d.users, d.disabledUsers ? d.disabledUsers + ' disabled' : 'all active'],
      ['deposit', 't-gold', 'Total deposited', tk(d.totalDeposited || 0), 'lifetime'],
      ['withdraw', 't-blue', 'Total withdrawn', tk(d.totalWithdrawn || 0), 'lifetime'],
      ['clock', 't-purple', 'Pending deposits', d.pendingDeposits, 'awaiting payment'],
      ['clock', 't-red', 'Pending withdraws', d.pendingWithdraws, 'awaiting payout'],
      ['alert', 't-red', 'Needs review', d.needsReview, d.needsReview ? 'check transactions' : 'all clear'],
    ];
    $('#stats').innerHTML = cards.map(function (c) {
      return '<section class="card stat-card"><span class="stat-ic ' + c[1] + '">' + AF.lotIcon(c[0], 22) + '</span><div><span class="label">' + E(c[2]) + '</span><b>' + E(c[3]) + '</b><small>' + E(c[4]) + '</small></div></section>';
    }).join('');
    AF.apply($('#stats'));
    $('#sys').innerHTML = '<div class="kv"><span class="muted">Maintenance mode</span><b>' + (d.maintenance ? 'ON' : 'off') + '</b></div>'
      + '<div class="kv"><span class="muted">TashPay merchant balance</span><b>' + (d.merchantBalance == null ? 'n/a' : E(d.merchantBalance)) + '</b></div>';
  });
  AF.api('admin/settings-get.php', undefined, { quiet: true }).then(function (j) {
    if (!j.success) { return; } var s = j.data.settings;
    $('#fees').innerHTML = '<div class="kv"><span class="muted">Deposit fee</span><b>' + E(s.depositFeePercent) + '%</b></div>'
      + '<div class="kv"><span class="muted">Withdraw fee</span><b>' + E(s.withdrawFeePercent) + '%</b></div>'
      + '<div class="kv"><span class="muted">Deposit limits</span><b>৳' + E(AF.num(s.minDeposit)) + ' – ৳' + E(AF.num(s.maxDeposit)) + '</b></div>'
      + '<div class="kv"><span class="muted">Withdraw limits</span><b>৳' + E(AF.num(s.minWithdraw)) + ' – ৳' + E(AF.num(s.maxWithdraw)) + '</b></div>';
  });
  AF.api('admin/transactions.php', {}).then(function (j) {
    if (!j.success) { $('#recent').innerHTML = '<div class="empty">Could not load transactions.</div>'; return; }
    var items = j.data.items.slice(0, 10);
    if (!items.length) { $('#recent').innerHTML = '<div class="empty">No transactions yet.</div>'; return; }
    $('#recent').innerHTML = '<div class="scroll-x"><table class="tbl"><tr><th>Date</th><th>Type</th><th>Amount</th><th>Fee</th><th>Status</th><th></th></tr>' + items.map(function (t) {
      return '<tr><td>' + E(dt(t.createdAtMs)) + '</td><td>' + E(t.type + ' · ' + t.method) + '</td><td>' + E(tk(t.amount)) + '</td><td>' + E(tk(t.fee)) + '</td><td>' + st(t.status) + (t.needsReview ? ' ' + AF.lotIcon('alert', 14) : '') + '</td><td><button class="link-btn" data-t="' + E(t.id) + '">Open</button></td></tr>';
    }).join('') + '</table></div>';
    AF.apply($('#recent'));
    $('#recent').addEventListener('click', function (e) { var b = e.target.closest('[data-t]'); if (b) { A.txModal(b.getAttribute('data-t')); } });
  });
});
