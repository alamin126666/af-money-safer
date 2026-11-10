/* Admin panel (English UI). Every call is re-checked on the server: session + admin_users role + audit log. */
AF.ready(function () {
  var $ = AF.$, pane = $('#pane'), E = AF.esc;
  var st = function (s) { return '<span class="pill ' + E(s) + '">' + E(s) + '</span>'; };
  var dt = function (ms) { return ms ? new Date(ms).toLocaleString() : '—'; };
  var tk = function (p) { return '৳' + AF.money(p); };

  var tabs = {
    overview: function () {
      pane.innerHTML = '<div class="card"><div class="empty">…</div></div>';
      AF.api('admin/overview.php').then(function (j) {
        if (!j.success) { return; } var d = j.data;
        var rows = [['Users', d.users], ['Disabled users', d.disabledUsers], ['Pending deposits', d.pendingDeposits], ['Pending withdraws', d.pendingWithdraws],
          ['Withdraws needing review', d.needsReview], ['TashPay merchant balance', d.merchantBalance == null ? 'n/a' : d.merchantBalance], ['Maintenance mode', d.maintenance ? 'ON' : 'off']];
        pane.innerHTML = '<div class="card">' + rows.map(function (r) { return '<div class="stat"><span class="muted">' + E(r[0]) + '</span><b>' + E(r[1]) + '</b></div>'; }).join('') + '</div>';
      });
    },
    users: function () {
      pane.innerHTML = '<div class="card"><form id="q" class="filters"><input class="txt" name="q" placeholder="Search email prefix"><button class="btn btn-primary">Search</button></form><div id="res"></div></div>';
      function run(q) {
        AF.api('admin/users.php', { q: q }).then(function (j) {
          if (!j.success) { return; }
          $('#res').innerHTML = '<div class="scroll-x"><table class="tbl"><tr><th>Name</th><th>Email</th><th>Status</th><th></th></tr>' + j.data.items.map(function (u) {
            return '<tr><td>' + E(u.fullName) + '</td><td>' + E(u.email) + '</td><td>' + st(u.status === 'active' ? 'successful' : 'failed') + '</td><td><button class="link-btn" data-u="' + E(u.uid) + '">Open</button></td></tr>'; }).join('') + '</table></div>';
        });
      }
      run(''); $('#q').addEventListener('submit', function (e) { e.preventDefault(); run(AF.form(e.target).q); });
      $('#res').addEventListener('click', function (e) { var b = e.target.closest('[data-u]'); if (b) { userModal(b.getAttribute('data-u')); } });
    },
    tx: function () {
      pane.innerHTML = '<div class="card"><form id="q" class="filters"><input class="txt" name="id" placeholder="Tx id or order id"><select name="status"><option value="">Any status</option><option>pending</option><option>processing</option><option>successful</option><option>failed</option><option>cancelled</option></select>' +
        '<select name="type"><option value="">Any type</option><option>deposit</option><option>withdraw</option></select><button class="btn btn-primary">Filter</button></form><div id="res"></div></div>';
      function run(f) {
        AF.api('admin/transactions.php', f).then(function (j) {
          if (!j.success) { return; }
          $('#res').innerHTML = '<div class="scroll-x"><table class="tbl"><tr><th>Date</th><th>Type</th><th>Amount</th><th>Status</th><th></th></tr>' + j.data.items.map(function (t) {
            return '<tr><td>' + E(dt(t.createdAtMs)) + '</td><td>' + E(t.type + ' · ' + t.method) + '</td><td>' + E(tk(t.amount)) + '</td><td>' + st(t.status) + (t.needsReview ? ' ⚠' : '') + '</td><td><button class="link-btn" data-t="' + E(t.id) + '">Open</button></td></tr>'; }).join('') + '</table></div>';
        });
      }
      run({}); $('#q').addEventListener('submit', function (e) { e.preventDefault(); run(AF.form(e.target)); });
      $('#res').addEventListener('click', function (e) { var b = e.target.closest('[data-t]'); if (b) { txModal(b.getAttribute('data-t')); } });
    },
    settings: function () {
      AF.api('admin/settings-get.php').then(function (j) {
        if (!j.success) { return; } var s = j.data.settings;
        function f(n, l, step) { return '<label class="field"><span>' + l + '</span><div class="input"><input name="' + n + '" value="' + E(s[n]) + '" inputmode="decimal" ' + (step || '') + '></div></label>'; }
        pane.innerHTML = '<form class="card" id="sf">' + f('minDeposit', 'Minimum deposit (BDT)') + f('maxDeposit', 'Maximum deposit (BDT)') + f('minWithdraw', 'Minimum withdraw (BDT, at least 100)') + f('maxWithdraw', 'Maximum withdraw (BDT)') +
          f('depositFeePercent', 'Deposit fee (%)') + f('withdrawFeePercent', 'Withdraw fee (%)') +
          '<label class="field"><span>Maintenance mode</span><div class="input"><input type="checkbox" name="maintenance" ' + (s.maintenance ? 'checked' : '') + ' style="flex:none;width:22px;height:22px"></div></label><button class="btn btn-primary btn-block">Save settings</button></form>';
        $('#sf').addEventListener('submit', function (e) { e.preventDefault(); var d = AF.form(e.target); d.maintenance = e.target.maintenance.checked; AF.api('admin/settings-update.php', d, { ok: true }); });
      });
    },
    audit: function () {
      AF.api('admin/audit-logs.php').then(function (j) {
        if (!j.success) { return; }
        pane.innerHTML = '<div class="card scroll-x"><table class="tbl"><tr><th>Time</th><th>Actor</th><th>Action</th><th>Target</th></tr>' + j.data.items.map(function (a) {
          return '<tr><td>' + E(dt(a.createdAtMs)) + '</td><td>' + E(String(a.actor).slice(0, 10)) + '</td><td>' + E(a.action) + '</td><td>' + E(a.target) + '</td></tr>'; }).join('') + '</table></div>';
      });
    }
  };

  function userModal(uid) {
    AF.api('admin/users.php', { uid: uid }).then(function (j) {
      if (!j.success) { return; } var u = j.data.user, w = j.data.wallet;
      var m = AF.modal({ title: u.fullName, html: '<p class="muted">' + E(u.email) + ' · ' + st(u.status) + '</p><div class="stat"><span>Balance</span><b>' + tk(w.balance) + '</b></div><div class="stat"><span>Held</span><b>' + tk(w.held) + '</b></div>' +
        '<div class="stat"><span>Deposited / Withdrawn</span><b>' + tk(w.totalDeposit) + ' / ' + tk(w.totalWithdraw) + '</b></div>' +
        '<h3 style="margin-top:12px">Account status</h3><form id="stf"><label class="field"><div class="input"><input name="reason" placeholder="Reason (required)"></div></label><button class="btn btn-ghost btn-block" data-s="' + (u.status === 'active' ? 'disabled' : 'active') + '">' + (u.status === 'active' ? 'Disable account' : 'Activate account') + '</button></form>' +
        '<h3 style="margin-top:12px">Balance adjustment (super admin)</h3><form id="adj"><label class="field"><div class="input"><input name="amount" placeholder="BDT, e.g. 500 or -500" inputmode="numeric"></div></label><label class="field"><div class="input"><input name="reason" placeholder="Reason (min 10 chars)"></div></label><label class="field"><div class="input"><input name="password" type="password" placeholder="Your password" autocomplete="current-password"></div></label><button class="btn btn-danger btn-block">Apply adjustment</button></form>' +
        '<h3 style="margin-top:12px">Last transactions</h3>' + j.data.transactions.map(function (t) { return '<div class="kv"><span>' + E(t.type + ' ' + tk(t.amount)) + '</span>' + st(t.status) + '</div>'; }).join('') });
      $('#stf', m.el).addEventListener('submit', function (e) { e.preventDefault(); var s = e.submitter ? e.submitter.getAttribute('data-s') : 'disabled';
        AF.api('admin/user-status.php', { uid: uid, status: s, reason: AF.form(e.target).reason }, { ok: true }).then(function (r) { if (r.success) { m.close(); } }); });
      $('#adj', m.el).addEventListener('submit', function (e) { e.preventDefault(); var d = AF.form(e.target); d.uid = uid;
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

  $('#tabs').addEventListener('click', function (e) {
    var b = e.target.closest('button'); if (!b) { return; }
    [].forEach.call(document.querySelectorAll('#tabs button'), function (x) { x.classList.toggle('on', x === b); }); tabs[b.getAttribute('data-t')]();
  });
  tabs.overview();
});
