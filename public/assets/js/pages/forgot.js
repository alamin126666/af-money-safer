AF.ready(function () {
  var f = AF.$('#f'), btn = AF.$('#submit');
  f.addEventListener('submit', function (e) {
    e.preventDefault();
    AF.busy(btn, true);
    AF.api('auth/forgot-password.php', { email: AF.form(f).email }).then(function (j) {
      AF.busy(btn, false);
      if (j.success) {
        sessionStorage.setItem('af_otp', JSON.stringify({ purpose: 'reset', email: j.data.email, resendAt: Date.now() + (j.data.resendIn || 60) * 1000, len: j.data.codeLength || 6 }));
        AF.toast(j.message, 'success'); AF.go('/verify.php');
      }
    });
  });
});
