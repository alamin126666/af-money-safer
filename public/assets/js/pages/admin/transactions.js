/* Admin · Transactions: filters + list + detail modal with ledger. */
AF.ready(function () {
  var $ = AF.$, A = window.AFAdmin, E = A.E, tk = A.tk, dt = A.dt, st = A.st;
  function run(f) {
    AF.api('admin/transactions.php', f).then(function (j) {
      if (!j.success) { return; }
      $('#res').innerHTML = '<div class="scroll-x"><table class="tbl"><tr><th>Date</th><th>Type</th><th>Amount</th><th>Fee</th><th>Status</th><th></th></tr>' + j.data.items.map(function (t) {
        return '<tr><td>' + E(dt(t.createdAtMs)) + '</td><td>' + E(t.type + ' · ' + t.method) + '</td><td>' + E(tk(t.amount)) + '</td><td>' + E(tk(t.fee)) + '</td><td>' + st(t.status) + (t.needsReview ? ' ' + AF.lotIcon('alert', 14) : '') + '</td><td><button class="link-btn" data-t="' + E(t.id) + '">Open</button></td></tr>';
      }).join('') + '</table></div>';
      AF.apply($('#res'));
    });
  }
  run({});
  $('#q').addEventListener('submit', function (e) { e.preventDefault(); run(AF.form(e.target)); });
  $('#res').addEventListener('click', function (e) { var b = e.target.closest('[data-t]'); if (b) { A.txModal(b.getAttribute('data-t')); } });
});
