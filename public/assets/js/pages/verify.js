AF.ready(function () {
  var st; try { st = JSON.parse(sessionStorage.getItem('af_otp') || 'null'); } catch (e) { st = null; }
  if (!st) { location.replace('/login.php'); return; }
  var btn = AF.$('#verifyBtn'), rs = AF.$('#resend'), pad, cd;
  function desc() { AF.$('#verifyDesc').textContent = AF.t('verify_desc', { email: st.email }); }
  desc(); document.addEventListener('af:lang', desc);

  function submit(code) {
    if (code.length !== (st.len || 6)) { AF.toast(AF.t('code_incomplete'), 'error'); return; }
    AF.busy(btn, true);
    AF.api('auth/verify-otp.php', { code: code }).then(function (j) {
      AF.busy(btn, false);
      if (j.success) {
        sessionStorage.removeItem('af_otp'); AF.toast(j.message, 'success');
        /* Admin login flow: the code was entered from /admin/login.php — only let
           real admins into the panel, everyone else goes back to the admin login. */
        if (st.dest) {
          AF.api('admin/overview.php', undefined, { quiet: true }).then(function (k) {
            if (k.success) { AF.go(st.dest); }
            else {
              AF.api('auth/logout.php', {});
              AF.toast(AF.lang() === 'bn' ? 'এই অ্যাকাউন্টটি অ্যাডমিন নয়।' : 'This account is not an admin.', 'error');
              setTimeout(function () { AF.go('/admin/login.php'); }, 1200);
            }
          });
          return;
        }
        AF.go(j.data.next === 'reset-password' ? '/reset-password.php' : '/dashboard.php'); return;
      }
      pad.shake(); pad.clear();
      if (j.code === 'NO_PENDING') { AF.go('/login.php'); }
    });
  }
  pad = AF.otpPad({ boxes: AF.$('#boxes'), pad: AF.$('#pad'), length: st.len || 6, onComplete: submit });
  btn.addEventListener('click', function () { submit(pad.get()); });

  cd = AF.countdown(rs, function () { return st.resendAt; });
  rs.addEventListener('click', function () {
    rs.disabled = true;
    AF.api('auth/resend-otp.php', {}).then(function (j) {
      if (j.success) {
        st.resendAt = Date.now() + (j.data.resendIn || 60) * 1000; sessionStorage.setItem('af_otp', JSON.stringify(st));
        AF.toast(j.message, 'success'); pad.clear();
      } else if (j.code === 'NO_PENDING') { AF.go('/login.php'); }
      else if (j.data && j.data.retryAfter) { st.resendAt = Date.now() + j.data.retryAfter * 1000; }
      cd.restart();
    });
  });
});
