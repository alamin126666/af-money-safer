AF.ready(function () {
  var f = AF.$('#f'), btn = AF.$('#submit'), cap = null;
  AF.boot().then(function (b) { if (b.authenticated) { location.replace('/dashboard.php'); } });
  AF.turnstile(AF.$('#captcha')).then(function (c) { cap = c; });
  f.addEventListener('submit', function (e) {
    e.preventDefault();
    var d = AF.form(f);
    if (!d.email || !d.password) { AF.toast(AF.t('login_sub'), 'error'); return; }
    if (!cap || !cap.token()) { AF.toast(AF.t('captcha_wait'), 'error'); return; }
    AF.busy(btn, true);
    AF.api('auth/login.php', { email: d.email, password: d.password, turnstileToken: cap.token() }).then(function (j) {
      AF.busy(btn, false);
      if (j.success || j.code === 'MAIL_FAILED') {
        sessionStorage.setItem('af_otp', JSON.stringify({ purpose: j.data.purpose, email: j.data.email, resendAt: Date.now() + (j.data.resendIn || 60) * 1000, len: j.data.codeLength || 6 }));
        if (j.success) { AF.toast(j.message, 'success'); }
        AF.go('/verify.php'); return;
      }
      cap.reset();
    });
  });
});
