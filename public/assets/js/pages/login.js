AF.ready(function () {
  var f = AF.$('#f'), btn = AF.$('#submit'), cap = null;
  AF.boot().then(function (b) { if (b.authenticated) { location.replace('/dashboard.php'); } });
  AF.turnstile(AF.$('#captcha')).then(function (c) { cap = c; });

  // ── Remember-me device ID ──
  function getDeviceId() {
    try {
      var k = 'af_did';
      var id = localStorage.getItem(k);
      if (!id) { id = 'dev_' + Math.random().toString(36).slice(2) + Date.now().toString(36); localStorage.setItem(k, id); }
      return id;
    } catch (e) { return ''; }
  }
  function getRememberMe() {
    try { return !!localStorage.getItem('af_rmb'); } catch (e) { return false; }
  }
  function setRememberMe(v) {
    try { if (v) { localStorage.setItem('af_rmb', '1'); } else { localStorage.removeItem('af_rmb'); } } catch (e) {}
  }

  // Restore checkbox state
  var rmb = AF.$('#rememberMe');
  if (rmb) { rmb.checked = getRememberMe(); }

  f.addEventListener('submit', function (e) {
    e.preventDefault();
    var d = AF.form(f);
    if (!d.email || !d.password) { AF.toast(AF.t('login_sub'), 'error'); return; }
    if (!cap || !cap.token()) { AF.toast(AF.t('captcha_wait'), 'error'); return; }

    var remember = rmb ? rmb.checked : false;
    setRememberMe(remember);

    var payload = { email: d.email, password: d.password, turnstileToken: cap.token() };
    if (remember) { payload.rememberMe = true; payload.deviceId = getDeviceId(); }

    AF.busy(btn, true);
    AF.api('auth/login.php', payload).then(function (j) {
      AF.busy(btn, false);
      // If trusted device: LOGIN_OK with next=dashboard — no OTP step
      if (j.success && j.data && j.data.next === 'dashboard') {
        AF.toast(j.message, 'success'); AF.go('/dashboard.php'); return;
      }
      if (j.success || j.code === 'MAIL_FAILED') {
        sessionStorage.setItem('af_otp', JSON.stringify({ purpose: j.data.purpose, email: j.data.email, resendAt: Date.now() + (j.data.resendIn || 60) * 1000, len: j.data.codeLength || 6 }));
        if (j.success) { AF.toast(j.message, 'success'); }
        AF.go('/verify.php'); return;
      }
      cap.reset();
    });
  });
});
