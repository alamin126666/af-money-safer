/* Admin · Audit log. */
AF.ready(function () {
  var E = AF.esc;
  AF.api('admin/audit-logs.php').then(function (j) {
    if (!j.success) { return; }
    document.getElementById('logs').innerHTML = '<table class="tbl"><tr><th>Time</th><th>Actor</th><th>Action</th><th>Target</th></tr>' + j.data.items.map(function (a) {
      return '<tr><td>' + E(a.createdAtMs ? new Date(a.createdAtMs).toLocaleString() : '—') + '</td><td>' + E(String(a.actor).slice(0, 10)) + '</td><td>' + E(a.action) + '</td><td>' + E(a.target) + '</td></tr>';
    }).join('') + '</table>';
  });
});
