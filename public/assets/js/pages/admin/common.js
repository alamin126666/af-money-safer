/* Shared admin helpers + detail modals. Loaded before each admin page script. */
window.AFAdmin = (function () {
  var E = function (s) { return AF.esc(s == null ? '' : String(s)); };
  var st = function (s) { return '<span class="pill ' + E(s) + '">' + E(s) + '</span>'; };
  var dt = function (ms) { return ms ? new Date(ms).toLocaleString() : '—'; };
  var tk = function (p) { return '৳' + AF.money(p); };

  function userModal(uid) {
    AF.api('admin/users.php', { uid: uid }).then(function (j) {
      if (!j.success) { return; } var u = j.data.user, w = j.data.wallet;
      var m = AF.modal({ title: u.fullName, html: '<p class="muted">' + E(u.email) + ' · ' + st(u.status) + '</p><div class="stat"><span>Balance</span><b>' + tk(w.balance) + '</b></div><div class="stat"><span>Held</span><b>' + tk(w.held) + '</b></div>' +
        '<div class="stat"><span>Deposited / Withdrawn</span><b>' + tk(w.totalDeposit) + ' / ' + tk(w.totalWithdraw) + '</b></div>' +
        '<h3 style="margin-top:12px">Account status</h3><form id="stf"><label class="field"><div class="input"><input name="reason" placeholder="Reason (required)"></div></label><button class="btn btn-ghost btn-block" data-s="' + (u.status === 'active' ? 'disabled' : 'active') + '">' + (u.status === 'active' ? 'Disable account' : 'Activate account') + '</button></form>' +
        '<h3 style="margin-top:12px">Balance adjustment (super admin)</h3><form id="adj"><label class="field"><div class="input"><input name="amount" placeholder="BDT, e.g. 500 or -500" inputmode="numeric"></div></label><label class="field"><div class="input"><input name="reason" placeholder="Reason (min 10 chars)"></div></label><label class="field"><div class="input"><input name="password" type="password" placeholder="Your password" autocomplete="current-password"></div></label><button class="btn btn-danger btn-block">Apply adjustment</button></form>' +
        '<h3 style="margin-top:12px">Last transactions</h3>' + j.data.transactions.map(function (t) { return '<div class="kv"><span>' + E(t.type + ' ' + tk(t.amount)) + '</span>' + st(t.status) + '</div>'; }).join('') });
      m.el.querySelector('#stf').addEventListener('submit', function (e) { e.preventDefault(); var s = e.submitter ? e.submitter.getAttribute('data-s') : 'disabled';
        AF.api('admin/user-status.php', { uid: uid, status: s, reason: AF.form(e.target).reason }, { ok: true }).then(function (r) { if (r.success) { m.close(); } }); });
      m.el.querySelector('#adj').addEventListener('submit', function (e) { e.preventDefault(); var d = AF.form(e.target); d.uid = uid;
        AF.api('admin/adjust-balance.php', d, { ok: true }).then(function (r) { if (r.success) { m.close(); } }); });
    });
  }

  function txModal(id) {
    AF.api('admin/transactions.php', { detail: id }).then(function (j) {
      if (!j.success) { return; } var t = j.data.tx;
      var rows = [['Tx id', t.id], ['Order id', t.orderId], ['User', t.userId], ['Type', t.type + ' / ' + t.method], ['Amount', tk(t.amount)], ['Fee', tk(t.fee)], ['Total', tk(t.total)], ['Status', t.status], ['Needs review', t.needsReview ? 'YES' : 'no'],
        ['Platform tx', t.platformTxId || '—'], ['Flow id', t.flowId || '—'], ['Wallet', t.walletNoFull || '—'], ['Holder', t.holderName || '—'], ['Error', t.errorMsg || '—'], ['Created', dt(t.createdAtMs)], ['Updated', dt(t.updatedAtMs)]];
      AF.modal({ title: 'Transaction', html: rows.map(function (r) { return '<div class="kv"><span class="muted">' + E(r[0]) + '</span><b style="word-break:break-all;text-align:right">' + E(r[1]) + '</b></div>'; }).join('') +
        '<h3 style="margin-top:10px">Ledger</h3>' + (j.data.ledger.map(function (l) { return '<div class="kv"><span>' + E(l.kind) + '</span><b>' + tk(l.amount) + '</b></div>'; }).join('') || '<p class="muted">none</p>') });
    });
  }

  return { E: E, st: st, dt: dt, tk: tk, userModal: userModal, txModal: txModal };
})();
