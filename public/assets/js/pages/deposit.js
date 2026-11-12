AF.ready(function () {
  var $ = AF.$, f = $('#f'), btn = $('#submit'), method = 'bKash', L = null, amt = f.querySelector('[name=amount]');
  AF.digits(amt, 9);

  /* ── 3-step wizard: 1 method → 2 amount → 3 success ──
     The step survives leaving the page: it is saved in sessionStorage, so
     coming back from the TashPay page without paying restores the amount
     step (method + amount kept), and a successful payment keeps the
     success step even if the user presses back and returns to this page. */
  var step = 1, activeTx = null, saved = null;
  try { saved = JSON.parse(sessionStorage.getItem('af_deposit') || 'null'); } catch (e) { saved = null; }
  function saveStep() {
    try { sessionStorage.setItem('af_deposit', JSON.stringify({ step: step, method: method, amount: amt.value, tx: activeTx || (saved && saved.tx) || null })); } catch (e) {}
  }
  function clearSaved() {
    saved = null;
    try { sessionStorage.removeItem('af_deposit'); } catch (e) {}
  }
  function showStep(n) {
    step = n;
    $('#stepMethod').hidden = n !== 1;
    $('#stepAmount').hidden = n !== 2;
    $('#formCard').hidden = n === 3;
    $('#statusCard').hidden = n !== 3;
    [].forEach.call(document.querySelectorAll('.stepper .st'), function (li) {
      var k = parseInt(li.getAttribute('data-n'), 10);
      li.classList.toggle('done', k < n);
      li.classList.toggle('on', k === n);
    });
    saveStep();
  }
  function paintMethod() {
    [].forEach.call(document.querySelectorAll('.method'), function (x) { x.classList.toggle('sel', x.getAttribute('data-m') === method); });
  }
  $('#toAmount').addEventListener('click', function () { showStep(2); });
  $('#backMethod').addEventListener('click', function () { showStep(1); });
  $('#depBack').addEventListener('click', function (e) {
    if (step === 2) { e.preventDefault(); showStep(1); }
    /* step 1 or 3: follow the link to the dashboard; the saved step is
       restored if the user comes back to this page. */
  });

  function methodMin() {
    if (!L) { return L ? L.minDeposit : 0; }
    return (L.methodLimits && L.methodLimits[method] && L.methodLimits[method].minDeposit)
      ? L.methodLimits[method].minDeposit : L.minDeposit;
  }

  /* Same formula as the server (Money::percent): fee in poisha, rounded up. */
  function fee(a) { return L ? Math.ceil(a * 100 * (L.depositFeePercent || 0) / 100 - 1e-9) : 0; }

  /* Live fee breakdown: what you pay, the fee, and what lands in your balance. */
  function summary() {
    var a = parseInt(amt.value || '0', 10), s = $('#summary');
    if (!a) { s.hidden = true; return; } s.hidden = false; var fp = fee(a);
    s.innerHTML = '<div class="row"><span class="muted">' + AF.esc(AF.t('fee_line')) + (L && L.depositFeePercent ? ' (' + L.depositFeePercent + '%)' : '') + '</span><b>৳' + AF.esc(AF.money(fp)) + '</b></div>'
      + '<div class="row"><span class="muted">' + AF.esc(AF.t('you_receive')) + '</span><b>৳' + AF.esc(AF.money(a * 100 - fp)) + '</b></div>';
  }
  amt.addEventListener('input', summary);

  function limits() {
    if (!L) { return; }
    var min = methodMin(), fee = L.depositFeePercent || 0;
    var txt = AF.t('limit_min_max', { min: AF.num(min), max: AF.num(L.maxDeposit) });
    if (fee > 0) { txt += ' · ' + fee + '% ' + (AF.lang() === 'bn' ? 'চার্জ' : 'fee'); }
    $('#limits').textContent = txt;
    summary();
  }

  function chips() {
    if (!L) { return; }
    var min = methodMin();
    $('#quick').innerHTML = '';
    [100, 300, 500, 1000, 2000, 5000].filter(function (v) { return v >= min && v <= L.maxDeposit; }).forEach(function (v) {
      var b = document.createElement('button'); b.type = 'button'; b.className = 'chip'; b.textContent = '৳' + AF.num(v);
      b.addEventListener('click', function () { amt.value = String(v); summary(); }); $('#quick').appendChild(b);
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
    paintMethod();
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
      if (j.success) { saveStep(); AF.toast(AF.t('redirecting'), 'success', 2500); setTimeout(function () { location.href = j.data.url; }, 600); }
      else { AF.busy(btn, false); }
    });
  });

  // Coming back from the TashPay cashier — or from a saved step.
  var tx = new URLSearchParams(location.search).get('tx');
  if (tx && /^[0-9a-f]{20}$/.test(tx)) {
    activeTx = tx; showStep(3); watch(tx);
  } else if (saved && saved.step) {
    if (saved.method === 'bKash' || saved.method === 'Nagad') { method = saved.method; paintMethod(); }
    if (saved.amount) { amt.value = String(saved.amount); }
    if (saved.step === 3 && saved.tx && /^[0-9a-f]{20}$/.test(saved.tx)) {
      activeTx = saved.tx; showStep(3); watch(saved.tx);
    } else {
      showStep(saved.step === 2 ? 2 : 1);
    }
  } else {
    showStep(1);
  }

  function watch(id) {
    var card = $('#statusCard'), tries = 0; card.hidden = false;
    /* Retry / new-deposit buttons inside the status card (delegated: the card
       is re-rendered on every poll). Stops the old poll from redrawing the
       card after the user leaves this transaction. */
    card.addEventListener('click', function (e) {
      if (e.target.closest('[data-retry]')) {
        activeTx = null; clearSaved(); showStep(2);
      } else if (e.target.closest('[data-new]')) {
        activeTx = null; clearSaved(); amt.value = ''; showStep(1);
      }
    });
    function draw(t) {
      if (activeTx !== id) { return; }
      var ok = t.status === 'successful', bad = t.status === 'failed' || t.status === 'cancelled';
      card.innerHTML = '<div class="status-big"><div class="ring' + (ok ? ' ring-anim' : '') + '" style="' + (ok ? 'color:var(--ok)' : bad ? 'color:var(--danger)' : 'color:var(--warn)') + '">'
        + (ok ? AF.animCheck(64) : AF.lotIcon(bad ? 'x' : 'deposit', 34)) + '</div><h3>'
        + AF.esc(AF.t(ok ? 'pay_ok' : bad ? 'pay_failed' : 'pay_wait')) + '</h3>'
        + (ok || bad ? '' : '<p class="muted">' + AF.esc(AF.t('pay_wait_hint')) + '</p>')
        + '<p><b>৳' + AF.esc(AF.money(t.amount)) + '</b> · ' + AF.esc(t.method) + '</p>'
        + (t.fee > 0 ? '<div class="summary" style="text-align:left"><div class="row"><span class="muted">' + AF.esc(AF.t('fee_line')) + '</span><b>৳' + AF.esc(AF.money(t.fee)) + '</b></div>'
        + '<div class="row"><span class="muted">' + AF.esc(AF.t('you_receive')) + '</span><b>৳' + AF.esc(AF.money(t.credited ? t.credited : t.amount - t.fee)) + '</b></div></div>' : '')
        + '<p><a class="btn btn-primary" href="/dashboard.php">' + AF.esc(AF.t('nav_home')) + '</a></p>'
        + (ok ? '<p style="margin-top:10px"><button class="btn btn-ghost btn-block" type="button" data-new>' + AF.esc(AF.t('new_deposit')) + '</button></p>'
          : bad ? '<p style="margin-top:10px"><button class="btn btn-ghost btn-block" type="button" data-retry>' + AF.esc(AF.t('try_again')) + '</button></p>' : '')
        + '</div>';
      AF.apply(card);
    }
    (function poll() {
      AF.api('deposit/status.php', { txId: id }, { quiet: true }).then(function (j) {
        if (activeTx !== id) { return; }
        if (j.success) {
          draw(j.data.tx); $('#balance').textContent = '৳' + AF.money(j.data.wallet.balance);
          if (['successful', 'failed', 'cancelled'].indexOf(j.data.tx.status) > -1) { return; }
        }
        if (++tries < 100) { setTimeout(poll, 3000); }
      });
    })();
  }
});
