AF.ready(function () {
  var $ = AF.$, f = $('#f'), btn = $('#submit'), method = 'bKash', L = null,
      amt = f.querySelector('[name=amount]'), wno = f.querySelector('[name=walletNo]');
  AF.digits(amt, 9);
  wno.addEventListener('input', function () { wno.value = wno.value.replace(/[^\d+]/g, '').slice(0, 14); });

  function methodMin() {
    if (!L) { return 0; }
    return (L.methodLimits && L.methodLimits[method] && L.methodLimits[method].minWithdraw)
      ? L.methodLimits[method].minWithdraw : L.minWithdraw;
  }

  function fee(a) { return L ? Math.ceil(a * 100 * L.withdrawFeePercent / 100 - 1e-9) : 0; }
  var bal = null;   /* wallet balance in poisha, from AF.me */

  function summary() {
    var a = parseInt(amt.value || '0', 10), s = $('#summary');
    if (!a) { s.hidden = true; return; } s.hidden = false; var fp = fee(a);
    s.innerHTML = '<div class="row"><span class="muted">' + AF.esc(AF.t('you_receive')) + '</span><b>৳' + AF.esc(AF.money(a * 100)) + '</b></div>'
      + '<div class="row"><span class="muted">' + AF.esc(AF.t('fee_line')) + '</span><b>৳' + AF.esc(AF.money(fp)) + '</b></div>'
      + '<div class="row"><span class="muted">' + AF.esc(AF.t('total_debit')) + '</span><b>৳' + AF.esc(AF.money(a * 100 + fp)) + '</b></div>';
  }
  amt.addEventListener('input', summary);

  function limits() {
    if (!L) { return; }
    var min = methodMin(), fee2 = L.withdrawFeePercent || 0;
    var txt = AF.t('limit_min_max', { min: AF.num(min), max: AF.num(L.maxWithdraw) });
    if (fee2 > 0) { txt += ' · ' + fee2 + '% ' + (AF.lang() === 'bn' ? 'চার্জ' : 'fee'); }
    $('#limits').textContent = txt;
    summary();
  }

  AF.me(true).then(function (me) {
    if (!me) { return; } L = me.limits; bal = me.wallet.balance;
    $('#balance').textContent = '৳' + AF.money(me.wallet.balance); AF.top(me); limits();
  });
  document.addEventListener('af:lang', limits);

  $('#methods').addEventListener('click', function (e) {
    var b = e.target.closest('.method'); if (!b) { return; }
    method = b.getAttribute('data-m');
    [].forEach.call(document.querySelectorAll('.method'), function (x) { x.classList.toggle('sel', x === b); });
    limits();  // update min hint when method changes
  });

  f.addEventListener('submit', function (e) {
    e.preventDefault();
    var d = AF.form(f);
    var a = parseInt((d.amount || '').trim(), 10);
    var min = methodMin();
    if (a < min) {
      AF.toast((AF.lang() === 'bn' ? 'সর্বনিম্ন ' : 'Minimum ') + '৳' + AF.num(min), 'error'); return;
    }
    /* The fee comes out of the balance too: withdrawing ৳100 needs ৳101 in the
       balance, so a ৳100 balance cannot withdraw ৳100. The server enforces the
       same rule; this stops the user one step earlier with a clear message. */
    var total = a * 100 + fee(a);
    if (bal != null && total > bal) {
      AF.toast((AF.lang() === 'bn' ? 'ব্যালেন্স যথেষ্ট নয়। ফি সহ মোট ৳' + AF.money(total) + ' লাগবে।' : 'Insufficient balance. You need ৳' + AF.money(total) + ' including the fee.'), 'error'); return;
    }
    AF.busy(btn, true);
    AF.api('withdraw/request-otp.php', { method: method, amount: d.amount.trim(), walletNo: d.walletNo, holderName: d.holderName }).then(function (j) {
      AF.busy(btn, false);
      if (j.success) { confirmStep(j.data); }
      else if (j.data && j.data.fields) { AF.toast(Object.keys(j.data.fields).map(function (k) { return j.data.fields[k]; }).join(' '), 'error'); }
    });
  });

  function confirmStep(info) {
    var sentAt = Date.now() + (info.resendIn || 60) * 1000, pad, cd, busy = false;
    var m = AF.modal({ title: AF.t('otp_title'), html:
      '<p class="muted">' + AF.esc(AF.t('otp_desc', { email: info.email })) + '</p>'
      + '<div class="summary">'
      + '<div class="row"><span class="muted">' + AF.esc(AF.t('you_receive')) + '</span><b>৳' + AF.esc(AF.money(info.amount)) + '</b></div>'
      + '<div class="row"><span class="muted">' + AF.esc(AF.t('fee_line')) + (L && L.withdrawFeePercent ? ' (' + L.withdrawFeePercent + '%)' : '') + '</span><b>৳' + AF.esc(AF.money(info.fee || 0)) + '</b></div>'
      + '<div class="row"><span class="muted">' + AF.esc(AF.t('total_debit')) + '</span><b>৳' + AF.esc(AF.money(info.total)) + '</b></div>'
      + '</div>'
      + '<div class="otp-boxes" id="mb"></div><div class="numpad" id="mp"></div>'
      + '<button class="btn btn-primary btn-block" id="mc" type="button">' + AF.esc(AF.t('confirm_withdraw')) + '</button>'
      + '<div class="resend"><button class="link-btn" id="mr" type="button" disabled></button></div>',
      onClose: function () { if (pad) { pad.destroy(); } }
    });
    function go(code) {
      if (busy || code.length !== 6) { return; } busy = true; AF.busy($('#mc', m.el), true);
      AF.api('withdraw/create.php', { code: code }).then(function (j) {
        busy = false; AF.busy($('#mc', m.el), false);
        if (j.success) { m.close(); AF.toast(j.message, 'success'); $('#formCard').hidden = true; watch(j.data.txId); return; }
        if (j.code === 'WITHDRAW_REJECTED' || j.code === 'INSUFFICIENT_BALANCE' || j.code === 'NO_PENDING') {
          m.close(); AF.me(true).then(function (me) { if (me) { $('#balance').textContent = '৳' + AF.money(me.wallet.balance); } }); return;
        }
        pad.shake(); pad.clear();
      });
    }
    pad = AF.otpPad({ boxes: $('#mb', m.el), pad: $('#mp', m.el), length: 6, onComplete: go });
    $('#mc', m.el).addEventListener('click', function () { go(pad.get()); });
    cd = AF.countdown($('#mr', m.el), function () { return sentAt; });
    $('#mr', m.el).addEventListener('click', function () {
      var d2 = AF.form(f);
      AF.api('withdraw/request-otp.php', { method: method, amount: d2.amount.trim(), walletNo: d2.walletNo, holderName: d2.holderName }).then(function (j) {
        if (j.success) { sentAt = Date.now() + (j.data.resendIn || 60) * 1000; AF.toast(j.message, 'success'); pad.clear(); }
        else if (j.data && j.data.retryAfter) { sentAt = Date.now() + j.data.retryAfter * 1000; }
        cd.restart();
      });
    });
  }

  function watch(id) {
    var card = $('#statusCard'), tries = 0; card.hidden = false;
    function draw(t) {
      var ok = t.status === 'successful', bad = t.status === 'failed' || t.status === 'cancelled';
      card.innerHTML = '<div class="status-big"><div class="ring' + (ok ? ' ring-anim' : '') + '" style="' + (ok ? 'color:var(--ok)' : bad ? 'color:var(--danger)' : 'color:var(--warn)') + '">'
        + (ok ? AF.animCheck(64) : AF.lotIcon(bad ? 'x' : 'withdraw', 34)) + '</div><h3>'
        + AF.esc(AF.t(ok ? 'wd_ok' : bad ? 'wd_failed' : 'wd_wait')) + '</h3>'
        + '<p><b>৳' + AF.esc(AF.money(t.amount)) + '</b> · ' + AF.esc(t.method) + ' · ' + AF.esc(t.walletNo || '') + '</p>'
        + '<span class="pill ' + AF.esc(t.status) + '">' + AF.esc(AF.t('s_' + t.status)) + '</span>'
        + '<p style="margin-top:14px"><a class="btn btn-primary" href="/dashboard.php">' + AF.esc(AF.t('nav_home')) + '</a></p></div>';
      AF.apply(card);
    }
    (function poll() {
      AF.api('withdraw/status.php', { txId: id }, { quiet: true }).then(function (j) {
        if (j.success) {
          draw(j.data.tx); $('#balance').textContent = '৳' + AF.money(j.data.wallet.balance);
          if (['successful', 'failed', 'cancelled'].indexOf(j.data.tx.status) > -1) { return; }
        }
        if (++tries < 120) { setTimeout(poll, 4000); }
      });
    })();
  }
});
