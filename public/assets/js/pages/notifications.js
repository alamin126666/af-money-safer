AF.ready(function () {
  var $ = AF.$, items = [];
  function text(n) {
    var p = {}; Object.keys(n.params || {}).forEach(function (k) { p[k] = k === 'amount' ? AF.money(n.params[k]) : n.params[k]; });
    return AF.t(n.key, p);
  }
  function draw() {
    var box = $('#list'); box.innerHTML = '';
    if (!items.length) { box.innerHTML = '<div class="empty">' + AF.esc(AF.t('no_notifications')) + '</div>'; return; }
    items.forEach(function (n) {
      var el = document.createElement('div'); el.className = 'tx'; el.style.cursor = 'default';
      el.innerHTML = '<div class="tx-ic ' + (/withdraw/.test(n.key) ? 'withdraw' : 'deposit') + '">' + AF.icon('bell') + '</div><div class="tx-main"><b' + (n.read ? ' class="muted" style="font-weight:500"' : '') + '></b><small>' + AF.esc(AF.date(n.createdAtMs)) + ' · ' + AF.esc(AF.time(n.createdAtMs)) + '</small></div>';
      el.querySelector('b').textContent = text(n); box.appendChild(el);
    });
  }
  AF.api('notifications/list.php').then(function (j) { if (j.success) { items = j.data.items; draw(); } });
  document.addEventListener('af:lang', draw);
  $('#markAll').addEventListener('click', function () {
    AF.api('notifications/mark-read.php', {}).then(function (j) { if (j.success) { items.forEach(function (n) { n.read = true; }); draw(); var b = $('#nbadge'); if (b) { b.hidden = true; } } });
  });
});
