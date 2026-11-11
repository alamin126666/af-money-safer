/* Admin · Users: search + list + detail modal (disable / balance adjust). */
AF.ready(function () {
  var $ = AF.$, A = window.AFAdmin, E = A.E, st = A.st;
  function run(q) {
    AF.api('admin/users.php', { q: q }).then(function (j) {
      if (!j.success) { return; }
      $('#res').innerHTML = '<div class="scroll-x"><table class="tbl"><tr><th>Name</th><th>Email</th><th>Status</th><th></th></tr>' + j.data.items.map(function (u) {
        return '<tr><td>' + E(u.fullName) + '</td><td>' + E(u.email) + '</td><td>' + st(u.status === 'active' ? 'successful' : 'failed') + '</td><td><button class="link-btn" data-u="' + E(u.uid) + '">Open</button></td></tr>';
      }).join('') + '</table></div>';
    });
  }
  run('');
  $('#q').addEventListener('submit', function (e) { e.preventDefault(); run(AF.form(e.target).q); });
  $('#res').addEventListener('click', function (e) { var b = e.target.closest('[data-u]'); if (b) { A.userModal(b.getAttribute('data-u')); } });
});
