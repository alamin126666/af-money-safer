/* Admin panel as a hash-routed app: every admin URL looks like /#/admin/...
   The hash is never sent to the server, so any page of the site can be the
   entry point — this script mounts the admin app over the current page when
   the hash starts with #/admin, and unmounts it when the hash leaves. */
(function () {
  var root = null, view = null, cap = null, who = null;

  function parse() {
    var p = location.hash.replace(/^#/, '').split('/').filter(Boolean);
    if (p[0] === 'admin-setup') { return 'setup'; }
    if (p[0] !== 'admin') { return null; }
    var v = p[1] || 'dashboard';
    if (v === 'setup') { return 'setup'; }
    if (['dashboard', 'users', 'transactions', 'settings', 'audit', 'login'].indexOf(v) === -1) { v = 'dashboard'; }
    return v;
  }

  var TITLES = { dashboard: 'Dashboard', users: 'Users', transactions: 'Transactions', settings: 'Settings', audit: 'Audit log', login: 'Admin Login', setup: 'Admin Setup' };
  var NAV = [['dashboard', 'Dashboard', 'home'], ['users', 'Users', 'user'], ['transactions', 'Transactions', 'history'], ['settings', 'Settings', 'settings'], ['audit', 'Audit log', 'clock']];

  function shellHTML(active) {
    var links = NAV.map(function (n) {
      return '<a href="#/admin/' + n[0] + '" class="' + (active === n[0] ? 'on' : '') + '"><span data-icon="' + n[2] + '"></span>' + n[1] + '</a>';
    }).join('');
    return '<div class="admin-shell">'
      + '<aside class="admin-side"><a class="admin-brand" href="#/admin/dashboard"><span data-logo="30"></span><span>AF MONEY SAFER <em>Admin</em></span></a>'
      + '<nav class="admin-nav" aria-label="Admin">' + links + '</nav>'
      + '<div class="admin-side-foot"><a href="/dashboard.php"><span data-icon="eye"></span>View site</a><a href="#" id="spaLogout"><span data-icon="logout"></span>Logout</a></div></aside>'
      + '<div class="admin-main"><header class="admin-top"><h1 id="spaTitle">' + (TITLES[active] || 'Admin') + '</h1>'
      + '<div class="admin-who"><span class="admin-ava" id="spaAva">A</span><span id="spaWho">Admin</span>'
      + '<a class="icon-btn" href="#" id="spaLogoutTop" aria-label="Logout"><span data-icon="logout"></span></a></div></header>'
      + '<main class="admin-content" id="spaContent"></main></div></div>';
  }

  function mount(v) {
    if (!root) {
      root = document.createElement('div');
      root.id = 'adminSpa';
      document.body.appendChild(root);
      document.body.style.overflow = 'hidden';
    }
    root.innerHTML = shellHTML(v);
    AF.apply(root);
    document.title = 'Admin · ' + (TITLES[v] || 'Admin') + ' | AF MONEY SAFER';
    var out = function (e) { e.preventDefault(); AF.api('auth/logout.php', {}).then(function () { location.hash = '#/admin/login'; }); };
    root.querySelector('#spaLogout').addEventListener('click', out);
    root.querySelector('#spaLogoutTop').addEventListener('click', out);
    if (who) { setWho(who); }
  }

  function unmount() {
    if (!root) { return; }
    root.remove(); root = null; view = null;
    document.body.style.overflow = '';
    document.title = 'AF MONEY SAFER';
  }

  function setWho(name) {
    who = name;
    if (!root) { return; }
    var w = root.querySelector('#spaWho'); if (w) { w.textContent = name + ' · Admin'; }
    var a = root.querySelector('#spaAva'); if (a) { a.textContent = (name || 'A').charAt(0).toUpperCase(); }
  }

  function content(html) {
    var c = root && root.querySelector('#spaContent');
    if (c) { c.innerHTML = html; AF.apply(c); }
    return c;
  }

  function go(v) { location.hash = '#/admin/' + v; }

  /* ---------- route guard: only logged-in admins see the protected views ---------- */
  function guard(next) {
    AF.boot().then(function (b) {
      if (!b.authenticated) { go('login'); return; }
      AF.api('admin/overview.php', undefined, { quiet: true }).then(function (j) {
        if (j.success) { next(); }
        else { AF.api('auth/logout.php', {}); go('login'); }
      });
    });
  }

  function loadWho() {
    AF.me(true).then(function (me) { if (me && me.user) { setWho(me.user.fullName || 'Admin'); } });
  }

  /* ---------- views ---------- */
  function vLogin() {
    content('<div class="auth-layout" style="min-height:0"><section class="auth-card" style="margin:0 auto">'
      + '<div class="auth-head"><div data-logo="64"></div><h1>Admin Login</h1><p class="muted">Sign in with your admin account to open the admin panel.</p></div>'
      + '<form id="spaLogin" novalidate autocomplete="on">'
      + '<label class="field"><span>Admin email</span><div class="input"><span data-icon="mail"></span><input type="email" name="email" autocomplete="email" inputmode="email" required></div></label>'
      + '<label class="field"><span>Password</span><div class="input"><span data-icon="lock"></span><input type="password" name="password" autocomplete="current-password" required>'
      + '<button type="button" class="eye" data-eye aria-label="Show/Hide"><span data-icon="eye"></span></button></div></label>'
      + '<div id="spaCaptcha" class="captcha"></div>'
      + '<button class="btn btn-primary btn-block" id="spaLoginBtn" type="submit">Login to admin panel</button></form>'
      + '<p class="auth-foot"><span class="muted">First time here?</span> <a href="#/admin-setup">Create the admin account</a></p>'
      + '</section></div>');
    var f = document.getElementById('spaLogin'), btn = document.getElementById('spaLoginBtn');
    cap = null;
    AF.turnstile(document.getElementById('spaCaptcha')).then(function (c) { cap = c; });
    AF.boot().then(function (b) {
      if (!b.authenticated) { return; }
      AF.api('admin/overview.php', undefined, { quiet: true }).then(function (j) { if (j.success) { go('dashboard'); } });
    });
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      var d = AF.form(f);
      if (!d.email || !d.password) { AF.toast('Enter your admin email and password.', 'error'); return; }
      if (!cap || !cap.token()) { AF.toast(AF.t('captcha_wait'), 'error'); return; }
      AF.busy(btn, true);
      AF.api('auth/login.php', { email: d.email, password: d.password, turnstileToken: cap.token() }).then(function (j) {
        AF.busy(btn, false);
        if (j.success && j.data && j.data.next === 'dashboard') {
          AF.api('admin/overview.php', undefined, { quiet: true }).then(function (k) {
            if (k.success) { AF.toast(j.message, 'success'); go('dashboard'); }
            else { AF.api('auth/logout.php', {}); AF.toast('This account is not an admin.', 'error'); }
          });
          return;
        }
        if (j.success || j.code === 'MAIL_FAILED') {
          sessionStorage.setItem('af_otp', JSON.stringify({ purpose: j.data.purpose, email: j.data.email, resendAt: Date.now() + (j.data.resendIn || 60) * 1000, len: j.data.codeLength || 6, dest: '/#/admin/dashboard' }));
          if (j.success) { AF.toast(j.message, 'success'); }
          AF.go('/verify.php'); return;
        }
        cap.reset();
      });
    });
  }

  function vSetup() {
    content('<div class="empty">…</div>');
    AF.api('admin/setup-status.php', undefined, { quiet: true, method: 'GET' }).then(function (j) {
      if (j.success && j.data.setupDone) {
        content('<div style="text-align:center;padding:60px 0"><h1 style="font-size:2.2rem;margin:0">404</h1><p class="muted">This page does not exist.</p><p><a class="btn btn-primary" href="#/admin/login">Go to admin login</a></p></div>');
        return;
      }
      content('<div class="auth-layout" style="min-height:0"><section class="auth-card" style="margin:0 auto">'
        + '<div class="auth-head"><div data-logo="64"></div><h1>Admin Setup</h1><p class="muted">Create the first admin account. This page works only once — after setup it disappears.</p></div>'
        + '<form id="spaSetup" novalidate autocomplete="off">'
        + '<label class="field"><span>Admin Gmail</span><div class="input"><span data-icon="mail"></span><input type="email" name="email" autocomplete="email" inputmode="email" placeholder="admin@gmail.com" required></div></label>'
        + '<label class="field"><span>Password</span><div class="input"><span data-icon="lock"></span><input type="password" name="password" autocomplete="new-password" required>'
        + '<button type="button" class="eye" data-eye aria-label="Show/Hide"><span data-icon="eye"></span></button></div>'
        + '<small class="muted">8+ characters, with a lowercase letter, an uppercase letter and a number.</small></label>'
        + '<label class="field"><span>Confirm password</span><div class="input"><span data-icon="lock"></span><input type="password" name="confirmPassword" autocomplete="new-password" required>'
        + '<button type="button" class="eye" data-eye aria-label="Show/Hide"><span data-icon="eye"></span></button></div></label>'
        + '<div id="spaCaptcha2" class="captcha"></div>'
        + '<button class="btn btn-primary btn-block" id="spaSetupBtn" type="submit">Register admin</button></form>'
        + '<p class="auth-foot"><span class="muted">Use a strong password you will remember — this is the only way to create an admin.</span></p>'
        + '</section></div>');
      var f = document.getElementById('spaSetup'), btn = document.getElementById('spaSetupBtn');
      cap = null;
      AF.turnstile(document.getElementById('spaCaptcha2')).then(function (c) { cap = c; });
      f.addEventListener('submit', function (e) {
        e.preventDefault();
        var d = AF.form(f);
        if (!d.email || !d.password || !d.confirmPassword) { AF.toast('Fill in the email and both password fields.', 'error'); return; }
        if (d.password !== d.confirmPassword) { AF.toast('The two passwords do not match.', 'error'); return; }
        if (!cap || !cap.token()) { AF.toast(AF.t('captcha_wait'), 'error'); return; }
        AF.busy(btn, true);
        AF.api('admin/setup-account.php', { email: d.email, password: d.password, confirmPassword: d.confirmPassword, turnstileToken: cap.token() }).then(function (j) {
          AF.busy(btn, false);
          if (j.success) { AF.toast(j.message, 'success'); setTimeout(function () { go('dashboard'); }, 800); return; }
          cap.reset();
        });
      });
    });
  }

  function statCard(ic, tone, label, value, sub) {
    return '<section class="card stat-card"><span class="stat-ic ' + tone + '">' + AF.icon(ic, 22) + '</span><div><span class="label">' + label + '</span><b>' + value + '</b><small>' + sub + '</small></div></section>';
  }

  function vDashboard() {
    content('<div class="stat-grid" id="spaStats"></div>'
      + '<div class="admin-grid2"><section class="card"><h3 style="margin-top:0">Fees &amp; limits</h3><div id="spaFees"><div class="empty">…</div></div><p style="margin:12px 0 0"><a class="btn btn-primary" href="#/admin/settings">Change fees</a></p></section>'
      + '<section class="card"><h3 style="margin-top:0">System</h3><div id="spaSys"><div class="empty">…</div></div></section></div>'
      + '<section class="card"><h3 style="margin-top:0">Recent transactions</h3><div id="spaRecent"><div class="empty">…</div></div></section>');
    var A = window.AFAdmin, E = A.E, tk = A.tk, dt = A.dt, st = A.st;
    AF.api('admin/overview.php', undefined, { quiet: true }).then(function (j) {
      if (!j.success) { return; } var d = j.data;
      var el = document.getElementById('spaStats'); if (!el) { return; }
      el.innerHTML = statCard('user', 't-green', 'Users', E(d.users), d.disabledUsers ? d.disabledUsers + ' disabled' : 'all active')
        + statCard('deposit', 't-gold', 'Total deposited', tk(d.totalDeposited || 0), 'lifetime')
        + statCard('withdraw', 't-blue', 'Total withdrawn', tk(d.totalWithdrawn || 0), 'lifetime')
        + statCard('clock', 't-purple', 'Pending deposits', E(d.pendingDeposits), 'awaiting payment')
        + statCard('clock', 't-red', 'Pending withdraws', E(d.pendingWithdraws), 'awaiting payout')
        + statCard('alert', 't-red', 'Needs review', E(d.needsReview), d.needsReview ? 'check transactions' : 'all clear');
      var sys = document.getElementById('spaSys'); if (sys) { sys.innerHTML = '<div class="kv"><span class="muted">Maintenance mode</span><b>' + (d.maintenance ? 'ON' : 'off') + '</b></div><div class="kv"><span class="muted">TashPay merchant balance</span><b>' + (d.merchantBalance == null ? 'n/a' : E(d.merchantBalance)) + '</b></div>'; }
    });
    AF.api('admin/settings-get.php', undefined, { quiet: true }).then(function (j) {
      if (!j.success) { return; } var s = j.data.settings, el = document.getElementById('spaFees'); if (!el) { return; }
      el.innerHTML = '<div class="kv"><span class="muted">Deposit fee</span><b>' + E(s.depositFeePercent) + '%</b></div>'
        + '<div class="kv"><span class="muted">Withdraw fee</span><b>' + E(s.withdrawFeePercent) + '%</b></div>'
        + '<div class="kv"><span class="muted">Deposit limits</span><b>৳' + E(AF.num(s.minDeposit)) + ' – ৳' + E(AF.num(s.maxDeposit)) + '</b></div>'
        + '<div class="kv"><span class="muted">Withdraw limits</span><b>৳' + E(AF.num(s.minWithdraw)) + ' – ৳' + E(AF.num(s.maxWithdraw)) + '</b></div>';
    });
    AF.api('admin/transactions.php', {}).then(function (j) {
      var el = document.getElementById('spaRecent'); if (!el) { return; }
      if (!j.success) { el.innerHTML = '<div class="empty">Could not load transactions.</div>'; return; }
      var items = j.data.items.slice(0, 10);
      if (!items.length) { el.innerHTML = '<div class="empty">No transactions yet.</div>'; return; }
      el.innerHTML = '<div class="scroll-x"><table class="tbl"><tr><th>Date</th><th>Type</th><th>Amount</th><th>Fee</th><th>Status</th><th></th></tr>' + items.map(function (t) {
        return '<tr><td>' + E(dt(t.createdAtMs)) + '</td><td>' + E(t.type + ' · ' + t.method) + '</td><td>' + E(tk(t.amount)) + '</td><td>' + E(tk(t.fee)) + '</td><td>' + st(t.status) + (t.needsReview ? ' ' + AF.icon('alert', 14) : '') + '</td><td><button class="link-btn" data-t="' + E(t.id) + '">Open</button></td></tr>';
      }).join('') + '</table></div>';
      el.addEventListener('click', function (e) { var b = e.target.closest('[data-t]'); if (b) { A.txModal(b.getAttribute('data-t')); } });
    });
  }

  function vUsers() {
    content('<section class="card"><form id="spaQ" class="filters" style="display:flex;gap:8px;margin-bottom:12px"><input class="txt" name="q" placeholder="Search email prefix" style="flex:1"><button class="btn btn-primary">Search</button></form><div id="spaRes"><div class="empty">…</div></div></section>');
    var A = window.AFAdmin, E = A.E, st = A.st;
    function run(q) {
      AF.api('admin/users.php', { q: q }).then(function (j) {
        if (!j.success) { return; }
        var el = document.getElementById('spaRes'); if (!el) { return; }
        el.innerHTML = '<div class="scroll-x"><table class="tbl"><tr><th>Name</th><th>Email</th><th>Status</th><th></th></tr>' + j.data.items.map(function (u) {
          return '<tr><td>' + E(u.fullName) + '</td><td>' + E(u.email) + '</td><td>' + st(u.status === 'active' ? 'successful' : 'failed') + '</td><td><button class="link-btn" data-u="' + E(u.uid) + '">Open</button></td></tr>';
        }).join('') + '</table></div>';
      });
    }
    run('');
    document.getElementById('spaQ').addEventListener('submit', function (e) { e.preventDefault(); run(AF.form(e.target).q); });
    document.getElementById('spaRes').addEventListener('click', function (e) { var b = e.target.closest('[data-u]'); if (b) { A.userModal(b.getAttribute('data-u')); } });
  }

  function vTransactions() {
    content('<section class="card"><form id="spaQ" class="filters" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px"><input class="txt" name="id" placeholder="Tx id or order id" style="flex:1;min-width:180px"><select name="status"><option value="">Any status</option><option>pending</option><option>processing</option><option>successful</option><option>failed</option><option>cancelled</option></select><select name="type"><option value="">Any type</option><option>deposit</option><option>withdraw</option></select><button class="btn btn-primary">Filter</button></form><div id="spaRes"><div class="empty">…</div></div></section>');
    var A = window.AFAdmin, E = A.E, tk = A.tk, dt = A.dt, st = A.st;
    function run(f) {
      AF.api('admin/transactions.php', f).then(function (j) {
        if (!j.success) { return; }
        var el = document.getElementById('spaRes'); if (!el) { return; }
        el.innerHTML = '<div class="scroll-x"><table class="tbl"><tr><th>Date</th><th>Type</th><th>Amount</th><th>Fee</th><th>Status</th><th></th></tr>' + j.data.items.map(function (t) {
          return '<tr><td>' + E(dt(t.createdAtMs)) + '</td><td>' + E(t.type + ' · ' + t.method) + '</td><td>' + E(tk(t.amount)) + '</td><td>' + E(tk(t.fee)) + '</td><td>' + st(t.status) + (t.needsReview ? ' ' + AF.icon('alert', 14) : '') + '</td><td><button class="link-btn" data-t="' + E(t.id) + '">Open</button></td></tr>';
        }).join('') + '</table></div>';
      });
    }
    run({});
    document.getElementById('spaQ').addEventListener('submit', function (e) { e.preventDefault(); run(AF.form(e.target)); });
    document.getElementById('spaRes').addEventListener('click', function (e) { var b = e.target.closest('[data-t]'); if (b) { A.txModal(b.getAttribute('data-t')); } });
  }

  function feePreviewBox() {
    return '<div class="summary" id="spaFeePrev"></div>';
  }
  function feePreview(form) {
    var el = form.querySelector('#spaFeePrev'); if (!el) { return; }
    var dp = parseFloat(form.depositFeePercent ? form.depositFeePercent.value : '0') || 0;
    var wp = parseFloat(form.withdrawFeePercent ? form.withdrawFeePercent.value : '0') || 0;
    var depFee = Math.ceil(1000 * 100 * dp / 100 - 1e-9);
    var wdFee = Math.ceil(100 * 100 * wp / 100 - 1e-9);
    el.innerHTML = '<div class="row"><span class="muted">Deposit ৳1,000 → fee</span><b>৳' + AF.money(depFee) + ' · user gets ৳' + AF.money(100000 - depFee) + '</b></div>'
      + '<div class="row"><span class="muted">Withdraw ৳100 → fee</span><b>৳' + AF.money(wdFee) + ' · total debit ৳' + AF.money(10000 + wdFee) + '</b></div>';
  }

  function vSettings() {
    content('<form class="card" id="spaSf"><div class="empty">…</div></form><p class="muted" style="font-size:.85rem">Changes apply to the live site within seconds. Deposit fee is taken from the amount the user pays; withdraw fee is charged on top of the amount, from the user\'s balance.</p>');
    var E = AF.esc;
    AF.api('admin/settings-get.php').then(function (j) {
      if (!j.success) { return; } var s = j.data.settings;
      var form = document.getElementById('spaSf'); if (!form) { return; }
      function f(n, l) { return '<label class="field"><span>' + l + '</span><div class="input"><input name="' + n + '" value="' + E(s[n]) + '" inputmode="decimal"></div></label>'; }
      form.innerHTML = '<h3 style="margin-top:0">Limits</h3>' + f('minDeposit', 'Minimum deposit (BDT)') + f('maxDeposit', 'Maximum deposit (BDT)') + f('minWithdraw', 'Minimum withdraw (BDT, at least 100)') + f('maxWithdraw', 'Maximum withdraw (BDT)')
        + '<h3>Fees</h3>' + f('depositFeePercent', 'Deposit fee (%)') + f('withdrawFeePercent', 'Withdraw fee (%)') + feePreviewBox()
        + '<h3>Maintenance</h3><label class="field"><span>Maintenance mode</span><div class="input"><input type="checkbox" name="maintenance" ' + (s.maintenance ? 'checked' : '') + ' style="flex:none;width:22px;height:22px"></div></label><button class="btn btn-primary btn-block">Save settings</button>';
      feePreview(form);
      form.addEventListener('input', function () { feePreview(form); });
      form.addEventListener('submit', function (e) { e.preventDefault(); var d = AF.form(e.target); d.maintenance = e.target.maintenance.checked; AF.api('admin/settings-update.php', d, { ok: true }); });
    });
  }

  function vAudit() {
    content('<section class="card scroll-x" id="spaLogs"><div class="empty">…</div></section>');
    var E = AF.esc;
    AF.api('admin/audit-logs.php').then(function (j) {
      if (!j.success) { return; }
      var el = document.getElementById('spaLogs'); if (!el) { return; }
      el.innerHTML = '<table class="tbl"><tr><th>Time</th><th>Actor</th><th>Action</th><th>Target</th></tr>' + j.data.items.map(function (a) {
        return '<tr><td>' + E(a.createdAtMs ? new Date(a.createdAtMs).toLocaleString() : '—') + '</td><td>' + E(String(a.actor).slice(0, 10)) + '</td><td>' + E(a.action) + '</td><td>' + E(a.target) + '</td></tr>';
      }).join('') + '</table>';
    });
  }

  var VIEWS = { dashboard: vDashboard, users: vUsers, transactions: vTransactions, settings: vSettings, audit: vAudit, login: vLogin, setup: vSetup };

  function render() {
    var v = parse();
    if (!v) { unmount(); return; }
    if (!root || view !== v) {
      mount(v);
      view = v;
      var t = root.querySelector('#spaTitle'); if (t) { t.textContent = TITLES[v]; }
      root.querySelectorAll('.admin-nav a').forEach(function (a) { a.classList.toggle('on', a.getAttribute('href') === '#/admin/' + v); });
      if (v === 'login' || v === 'setup') { VIEWS[v](); return; }
      content('<div class="empty">…</div>');
      guard(function () { loadWho(); VIEWS[v](); });
      return;
    }
    /* same view, hash re-set: ignore */
  }

  AF.ready(function () {
    render();
    window.addEventListener('hashchange', render);
  });
})();
