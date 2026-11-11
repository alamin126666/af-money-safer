/* One-time admin setup: email + password -> first admin account, then straight to the admin panel. */
AF.ready(function () {
  var f = AF.$('#f'), btn = AF.$('#submit'), cap = null;
  AF.turnstile(AF.$('#captcha')).then(function (c) { cap = c; });
  f.addEventListener('submit', function (e) {
    e.preventDefault();
    var d = AF.form(f);
    if (!d.email || !d.password || !d.confirmPassword) { AF.toast('Fill in the email and both password fields.', 'error'); return; }
    if (d.password !== d.confirmPassword) { AF.toast('The two passwords do not match.', 'error'); return; }
    if (!cap || !cap.token()) { AF.toast(AF.t('captcha_wait'), 'error'); return; }
    AF.busy(btn, true);
    AF.api('admin/setup-account.php', { email: d.email, password: d.password, confirmPassword: d.confirmPassword, turnstileToken: cap.token() }).then(function (j) {
      AF.busy(btn, false);
      if (j.success) { AF.toast(j.message, 'success'); setTimeout(function () { location.replace('/#/admin/dashboard'); }, 800); return; }
      cap.reset();
    });
  });
});
