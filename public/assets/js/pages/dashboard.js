AF.ready(function () {
  var $ = AF.$, me = null;
  function fill() {
    if (!me) { return; }
    var n = $('#userName'); n.classList.remove('skeleton-text'); n.textContent = me.user.fullName;
    $('#balance').textContent = '৳' + AF.money(me.wallet.balance);
    $('#totalDeposit').textContent = '৳' + AF.money(me.wallet.totalDeposit);
    $('#totalWithdraw').textContent = '৳' + AF.money(me.wallet.totalWithdraw);
    AF.top(me);
  }
  function refresh() { return AF.me(true).then(function (m) { if (m) { me = m; fill(); } }); }
  refresh();
  AFHistory.mount($('#history'));
  setInterval(function () { if (!document.hidden) { refresh(); } }, 30000);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) { refresh(); } });
  document.addEventListener('af:lang', fill);
});
