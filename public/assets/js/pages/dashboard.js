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
    toggle.innerHTML = AF.icon(hidden ? 'eyeoff' : 'eye', 20);
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
});
