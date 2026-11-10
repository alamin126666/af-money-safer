AF.ready(function () {
  var f = AF.$('#f'), btn = AF.$('#submit'), cap = null;
  AF.turnstile(AF.$('#captcha')).then(function (c) { cap = c; });
  f.addEventListener('submit', function (e) {
    e.preventDefault();
    var d = AF.form(f); AF.fieldErrors(f, null);
    if (d.password !== d.confirmPassword) { AF.fieldErrors(f, { confirmPassword: AF.lang() === 'bn' ? 'দুটি পাসওয়ার্ড মেলেনি।' : 'The two passwords do not match.' }); return; }
    if (!cap || !cap.token()) { AF.toast(AF.t('captcha_wait'), 'error'); return; }
    AF.busy(btn, true);
    AF.api('auth/register.php', { fullName: d.fullName, email: d.email, password: d.password, confirmPassword: d.confirmPassword, turnstileToken: cap.token() }).then(function (j) {
      AF.busy(btn, false);
      if (j.success || j.code === 'MAIL_FAILED') {
        sessionStorage.setItem('af_otp', JSON.stringify({ purpose: 'register', email: j.data.email, resendAt: Date.now() + (j.data.resendIn || 60) * 1000, len: j.data.codeLength || 6 }));
        if (j.success) { AF.toast(j.message, 'success'); }
        AF.go('/verify.php'); return;
      }
      if (j.data && j.data.fields) { AF.fieldErrors(f, j.data.fields); }
      cap.reset();
    });
  });
});
