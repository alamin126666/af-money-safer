AF.ready(function () {
  var f = AF.$('#f'), btn = AF.$('#submit');
  f.addEventListener('submit', function (e) {
    e.preventDefault(); AF.fieldErrors(f, null);
    var d = AF.form(f); AF.busy(btn, true);
    AF.api('auth/reset-password.php', { newPassword: d.newPassword, confirmPassword: d.confirmPassword }).then(function (j) {
      AF.busy(btn, false);
      if (j.success) { AF.toast(j.message, 'success'); setTimeout(function () { AF.go('/login.php'); }, 900); }
      else if (j.data && j.data.fields) { AF.fieldErrors(f, j.data.fields); }
      else if (j.code === 'RESET_SESSION_INVALID') { setTimeout(function () { AF.go('/forgot-password.php'); }, 1200); }
    });
  });
});
