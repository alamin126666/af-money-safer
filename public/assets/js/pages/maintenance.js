/* Maintenance page: reason + live countdown; back to login when it ends or maintenance is off. */
AF.ready(function () {
  var untilMs = 0;
  function fmt(n) { return (n < 10 ? '0' : '') + n; }
  function tick() {
    if (!untilMs) { return; }
    var ms = untilMs - Date.now();
    if (ms <= 0) { location.replace('/login.php'); return; }
    var s = Math.floor(ms / 1000);
    var d = Math.floor(s / 86400); s -= d * 86400;
    var h = Math.floor(s / 3600); s -= h * 3600;
    var m = Math.floor(s / 60); s -= m * 60;
    var cdD = document.getElementById('cdD');
    if (cdD) { cdD.style.display = d > 0 ? '' : 'none'; cdD.querySelector('b').textContent = d; }
    var hEl = document.getElementById('cdH'); if (hEl) { hEl.textContent = fmt(h); }
    var mEl = document.getElementById('cdM'); if (mEl) { mEl.textContent = fmt(m); }
    var sEl = document.getElementById('cdS'); if (sEl) { sEl.textContent = fmt(s); }
  }
  function apply(b) {
    var mt = (b && b.maintenance) || {};
    if (!mt.on) { location.replace('/login.php'); return; }
    untilMs = mt.untilMs || 0;
    var r = document.getElementById('mReason');
    if (r) {
      if (mt.reason) { r.hidden = false; document.getElementById('mReasonText').textContent = mt.reason; }
      else { r.hidden = true; }
    }
    var cd = document.getElementById('mCd'); if (cd) { cd.style.display = untilMs ? '' : 'none'; }
    var backRow = document.getElementById('mBackRow'); if (backRow) { backRow.style.display = untilMs ? '' : 'none'; }
    var back = document.getElementById('mBackAt');
    if (back && untilMs) { back.textContent = new Date(untilMs).toLocaleString(); }
    tick();
  }
  AF.boot().then(apply);
  setInterval(function () { AF.boot(true).then(apply); }, 20000);
  setInterval(tick, 1000);
});
