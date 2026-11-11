/* Admin login: same email + password + OTP flow as the user login, but it lands
   in the admin panel — and refuses accounts that are not admins. */
AF.ready(function () {
  var f = AF.$('#f'), btn = AF.$('#submit'), cap = null;
  AF.boot().then(function (b) {
    if (b.authenticated) {
      AF.api('admin/overview.php', undefined, { quiet: true }).then(function (j) { if (j.success) { location.replace('/#/admin/dashboard'); } });
    }
  });
  AF.turnstile(AF.$('#captcha')).then(function (c) { cap = c; });

  function notAdmin() {
    AF.toast(AF.lang() === 'bn' ? 'এই অ্যাকাউন্টটি অ্যাডমিন নয়।' : 'This account is not an admin.', 'error');
  }

  f.addEventListener('submit', function (e) {
    e.preventDefault();
    var d = AF.form(f);
    if (!d.email || !d.password) { AF.toast('Enter your admin email and password.', 'error'); return; }
    if (!cap || !cap.token()) { AF.toast(AF.t('captcha_wait'), 'error'); return; }
    AF.busy(btn, true);
    AF.api('auth/login.php', { email: d.email, password: d.password, turnstileToken: cap.token() }).then(function (j) {
      AF.busy(btn, false);
      // Trusted device: logged in already — check the account is really an admin.
      if (j.success && j.data && j.data.next === 'dashboard') {
        AF.api('admin/overview.php', undefined, { quiet: true }).then(function (k) {
          if (k.success) { AF.toast(j.message, 'success'); AF.go('/#/admin/dashboard'); }
          else { AF.api('auth/logout.php', {}); notAdmin(); }
        });
        return;
      }
      // OTP step: remember where to land after the code is verified.
      if (j.success || j.code === 'MAIL_FAILED') {
        sessionStorage.setItem('af_otp', JSON.stringify({ purpose: j.data.purpose, email: j.data.email, resendAt: Date.now() + (j.data.resendIn || 60) * 1000, len: j.data.codeLength || 6, dest: '/#/admin/dashboard' }));
        if (j.success) { AF.toast(j.message, 'success'); }
        AF.go('/verify.php'); return;
      }
      cap.reset();
    });
  });
});
