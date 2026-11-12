AF.ready(function () {
  var $ = AF.$, me = null, hidden = false, firstFill = true, lastLang = null;
  try { hidden = localStorage.getItem('af_hide_balance') === '1'; } catch (e) { hidden = false; }

  /* Balance hide/show toggle — injected into the balance card label if not already present. */
  var balLabel = $('.balance-card .label');
  var toggle = $('#balToggle') || $('#balanceToggle');
  if (!toggle && balLabel) {
    toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.id = 'balanceToggle';
    toggle.className = 'icon-btn balance-toggle';
    balLabel.appendChild(toggle);
  }
  function paintToggle() {
    if (!toggle) { return; }
    toggle.innerHTML = AF.lotIcon(hidden ? 'eyeoff' : 'eye', 20);
    AF.lottieFill(toggle.querySelector('[data-icon]'));
    toggle.setAttribute('aria-pressed', hidden ? 'true' : 'false');
    toggle.setAttribute('aria-label', hidden ? 'Show balance' : 'Hide balance');
    toggle.title = hidden ? 'Show balance' : 'Hide balance';
  }
  if (toggle) {
    toggle.addEventListener('click', function () {
      hidden = !hidden;
      try { localStorage.setItem('af_hide_balance', hidden ? '1' : '0'); } catch (e) {}
      paintToggle();
      fill();
    });
  }
  paintToggle();

  var MASK = '৳ ••••••';
  function setAmount(el, paisa) {
    if (!el) { return; }
    if (hidden) { el.textContent = MASK; el.classList.remove('pop'); delete el.dataset.v; return; }
    if (firstFill || !el.dataset.v || el.dataset.v !== String(paisa) || el.textContent === MASK) {
      AF.countUp(el, paisa);
      el.classList.remove('pop'); void el.offsetWidth; el.classList.add('pop');
    }
    el.dataset.v = String(paisa);
  }
  function fill() {
    if (!me) { return; }
    if (lastLang && lastLang !== AF.lang()) {
      ['#balance', '#totalDeposit', '#totalWithdraw'].forEach(function (s) { var e = $(s); if (e) { delete e.dataset.v; } });
    }
    lastLang = AF.lang();
    var n = $('#userName');
    if (n) { n.classList.remove('skeleton-text'); n.textContent = me.user.fullName; }
    setAmount($('#balance'), me.wallet.balance);
    setAmount($('#totalDeposit'), me.wallet.totalDeposit);
    setAmount($('#totalWithdraw'), me.wallet.totalWithdraw);
    firstFill = false;
    AF.top(me);
  }
  function refresh() { return AF.me(true).then(function (m) { if (m) { me = m; fill(); } }); }
  refresh();
  setInterval(function () { if (!document.hidden) { refresh(); } }, 30000);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) { refresh(); } });
  document.addEventListener('af:lang', fill);

  /* ── 14-day deposit/withdraw chart (hand-rolled canvas, no library) ── */
  var chartData = null, cv = $('#chartCv');
  function drawChart() {
    if (!cv || !chartData) { return; }
    var days = chartData.days || [];
    var empty = $('#chartEmpty');
    var hasData = days.some(function (d) { return d.deposit > 0 || d.withdraw > 0; });
    if (empty) { empty.hidden = hasData; }
    var ctx = cv.getContext('2d');
    var w = cv.clientWidth || cv.parentNode.clientWidth || 320, h = 170, dpr = window.devicePixelRatio || 1;
    cv.width = w * dpr; cv.height = h * dpr; cv.style.height = h + 'px';
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, w, h);
    var cs = getComputedStyle(document.documentElement);
    var depC = (cs.getPropertyValue('--ok') || '#12a36b').trim();
    var wdC = (cs.getPropertyValue('--danger') || '#e0524a').trim();
    var mutC = (cs.getPropertyValue('--muted') || '#888888').trim();
    var max = 0;
    days.forEach(function (d) { max = Math.max(max, d.deposit, d.withdraw); });
    if (max <= 0) { return; }
    var padL = 6, padB = 20, padT = 8, plotH = h - padB - padT;
    var gw = (w - padL * 2) / days.length, bw = Math.max(3, Math.min(12, (gw - 6) / 2));
    function bar(x, y, bw2, bh) {
      if (bh <= 0) { return; }
      if (ctx.roundRect) { ctx.beginPath(); ctx.roundRect(x, y, bw2, bh, 2); ctx.fill(); }
      else { ctx.fillRect(x, y, bw2, bh); }
    }
    days.forEach(function (d, i) {
      var x0 = padL + i * gw + (gw - bw * 2 - 3) / 2;
      var dh = d.deposit > 0 ? Math.max(2, d.deposit / max * plotH) : 0;
      var wh = d.withdraw > 0 ? Math.max(2, d.withdraw / max * plotH) : 0;
      ctx.fillStyle = depC; bar(x0, padT + plotH - dh, bw, dh);
      ctx.fillStyle = wdC; bar(x0 + bw + 3, padT + plotH - wh, bw, wh);
      ctx.fillStyle = mutC;
      ctx.font = '10px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText(String(new Date(d.date + 'T00:00:00+06:00').getDate()), x0 + bw + 1.5, h - 6);
    });
  }
  function loadChart() {
    if (!cv) { return; }
    AF.api('dashboard/chart.php', undefined, { quiet: true }).then(function (j) {
      if (!j.success) { return; }
      chartData = j.data; drawChart();
    });
  }
  loadChart();
  document.addEventListener('af:lang', drawChart);
  document.addEventListener('af:theme', drawChart);
  window.addEventListener('resize', drawChart);

  /* ── Promo code redeem ── */
  var promoForm = $('#promoForm');
  if (promoForm) {
    promoForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var d = AF.form(promoForm);
      if (!d.code) { return; }
      var b = promoForm.querySelector('button'); AF.busy(b, true);
      AF.api('promo/redeem.php', { code: d.code }).then(function (j) {
        AF.busy(b, false);
        if (j.success) { promoForm.reset(); refresh(); }
      });
    });
  }
});
