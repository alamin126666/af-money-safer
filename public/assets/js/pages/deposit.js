AF.ready(function () {
  var $ = AF.$, f = $('#f'), btn = $('#submit'), method = 'bKash', L = null, amt = f.querySelector('[name=amount]');
  AF.digits(amt, 9);

  function methodMin() {
    if (!L) { return L ? L.minDeposit : 0; }
    return (L.methodLimits && L.methodLimits[method] && L.methodLimits[method].minDeposit)
      ? L.methodLimits[method].minDeposit : L.minDeposit;
  }

  function limits() {
    if (!L) { return; }
    var min = methodMin(), fee = L.depositFeePercent || 0;
    var txt = AF.t('limit_min_max', { min: AF.num(min), max: AF.num(L.maxDeposit) });
    if (fee > 0) { txt += ' · ' + fee + '% ' + (AF.lang() === 'bn' ? 'চার্জ' : 'fee'); }
    $('#limits').textContent = txt;
  }

  function chips() {
    if (!L) { return; }
    var min = methodMin();
    $('#quick').innerHTML = '';
    [100, 300, 500, 1000, 2000, 5000].filter(function (v) { return v >= min && v <= L.maxDeposit; }).forEach(function (v) {
      var b = document.createElement('button'); b.type = 'button'; b.className = 'chip'; b.textContent = '৳' + AF.num(v);
      b.addEventListener('click', function () { amt.value = String(v); }); $('#quick').appendChild(b);
    });
  }

  AF.me(true).then(function (me) {
    if (!me) { return; } L = me.limits;
    $('#balance').textContent = '৳' + AF.money(me.wallet.balance); AF.top(me); limits(); chips();
  });
  document.addEventListener('af:lang', function () { limits(); chips(); });

  $('#methods').addEventListener('click', function (e) {
    var b = e.target.closest('.method'); if (!b) { return; }
    method = b.getAttribute('data-m');
    [].forEach.call(document.querySelectorAll('.method'), function (x) { x.classList.toggle('sel', x === b); });
    limits(); chips();  // update hint when method changes
  });

  f.addEventListener('submit', function (e) {
    e.preventDefault();
    var a = amt.value.trim();
    if (!/^[1-9]\d{0,8}$/.test(a)) { AF.toast(AF.lang() === 'bn' ? 'পরিমাণ সঠিক নয়।' : 'Invalid amount.', 'error'); return; }
    var min = methodMin();
    if (parseInt(a, 10) < min) {
      AF.toast((AF.lang() === 'bn' ? 'সর্বনিম্ন ' : 'Minimum ') + '৳' + AF.num(min), 'error'); return;
    }
    AF.busy(btn, true);
    AF.api('deposit/create.php', { amount: a, method: method }).then(function (j) {
      if (j.success) { AF.toast(AF.t('redirecting'), 'success', 2500); setTimeout(function () { location.href = j.data.url; }, 600); }
      else { AF.busy(btn, false); }
    });
  });

  // Coming back from the TashPay cashier
  var tx = new URLSearchParams(location.search).get('tx');
  if (tx && /^[0-9a-f]{20}$/.test(tx)) { $('#formCard').hidden = true; watch(tx); }

  function watch(id) {
    var card = $('#statusCard'), tries = 0; card.hidden = false;
    function draw(t) {
      var ok = t.status === 'successful', bad = t.status === 'failed' || t.status === 'cancelled';
      card.innerHTML = '<div class="status-big"><div class="ring" style="' + (ok ? 'color:var(--ok)' : bad ? 'color:var(--danger)' : 'color:var(--warn)') + '">'
        + AF.icon(ok ? 'check' : bad ? 'x' : 'deposit', 34) + '</div><h3>'
        + AF.esc(AF.t(ok ? 'pay_ok' : bad ? 'pay_failed' : 'pay_wait')) + '</h3>'
        + (ok || bad ? '' : '<p class="muted">' + AF.esc(AF.t('pay_wait_hint')) + '</p>')
        + '<p><b>৳' + AF.esc(AF.money(t.amount)) + '</b> · ' + AF.esc(t.method) + '</p>'
        + '<a class="btn btn-primary" href="/dashboard.php">' + AF.esc(AF.t('nav_home')) + '</a></div>';
    }
    (function poll() {
      AF.api('deposit/status.php', { txId: id }, { quiet: true }).then(function (j) {
        if (j.success) {
          draw(j.data.tx); $('#balance').textContent = '৳' + AF.money(j.data.wallet.balance);
          if (['successful', 'failed', 'cancelled'].indexOf(j.data.tx.status) > -1) { return; }
        }
        if (++tries < 100) { setTimeout(poll, 3000); }
      });
    })();
  }
});
