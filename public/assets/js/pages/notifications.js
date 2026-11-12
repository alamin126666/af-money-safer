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
      el.innerHTML = '<div class="tx-ic ' + (/withdraw/.test(n.key) ? 'withdraw' : 'deposit') + '">' + AF.lotIcon('bell') + '</div><div class="tx-main"><b' + (n.read ? ' class="muted" style="font-weight:500"' : '') + '></b><small>' + AF.esc(AF.date(n.createdAtMs)) + ' · ' + AF.esc(AF.time(n.createdAtMs)) + '</small></div>';
      if (n.key === 'n.broadcast') {
        /* Broadcast: the title is the bold line, the message is the body (text only, never raw HTML). */
        el.querySelector('b').textContent = (n.params && n.params.title) || AF.t('broadcast');
        var msg = document.createElement('span'); msg.className = 'bc-msg';
        msg.textContent = (n.params && n.params.message) || '';
        el.querySelector('.tx-main').insertBefore(msg, el.querySelector('small'));
      } else {
        el.querySelector('b').textContent = text(n);
      }
      box.appendChild(el);
    });
    AF.apply(box);
  }
  /* Seen = read: opening the page clears the unread count to 0 everywhere
     (top-bar badge included), and the server count is re-synced afterwards. */
  function markSeen() {
    AF.api('notifications/mark-read.php', {}).then(function (j) {
      if (!j.success) { return; }
      items.forEach(function (n) { n.read = true; }); draw();
      var b = $('#nbadge'); if (b) { b.hidden = true; b.textContent = '0'; }
      AF.me(true).then(AF.top);
    });
  }
  AF.api('notifications/list.php').then(function (j) {
    if (!j.success) { return; }
    items = j.data.items; draw();
    if (items.some(function (n) { return !n.read; })) { markSeen(); }
  });
  document.addEventListener('af:lang', draw);
  $('#markAll').addEventListener('click', markSeen);
});
