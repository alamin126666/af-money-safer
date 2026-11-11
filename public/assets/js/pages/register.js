AF.ready(function () {
  var f = AF.$('#f'), btn = AF.$('#submit'), cap = null;

  /* Password-strength meter — uses .pw-meter[data-s] + .pw-meter-label if present,
     otherwise injects them right after the password field. Score 0-4. */
  var pw = f ? f.querySelector('input[name="password"]') : null;
  var meter = document.querySelector('.pw-meter');
  var meterLabel = document.querySelector('.pw-meter-label');
  if (pw && !meter) {
    var field = pw.closest('.field') || pw.parentNode;
    meter = document.createElement('div');
    meter.className = 'pw-meter';
    meter.setAttribute('data-s', '0');
    meter.innerHTML = '<i></i><i></i><i></i><i></i>';
    meterLabel = document.createElement('span');
    meterLabel.className = 'pw-meter-label';
    var anchor = pw.closest('.input');
    if (anchor && anchor.parentNode) { anchor.parentNode.insertBefore(meter, anchor.nextSibling); meter.parentNode.insertBefore(meterLabel, meter.nextSibling); }
    else if (field) { field.appendChild(meter); field.appendChild(meterLabel); }
  }
  var LABELS = { bn: ['', 'দুর্বল', 'মোটামুটি', 'ভালো', 'শক্তিশালী'], en: ['', 'Weak', 'Fair', 'Good', 'Strong'] };
  function scorePw(v) {
    if (!v) { return 0; }
    var s = 0;
    if (v.length >= 8) { s++; }
    if (/[a-z]/.test(v) && /[A-Z]/.test(v)) { s++; }
    if (/\d/.test(v)) { s++; }
    if (/[^A-Za-z0-9]/.test(v) || v.length >= 12) { s++; }
    return Math.min(4, s);
  }
  function paintMeter() {
    if (!meter || !pw) { return; }
    var s = scorePw(pw.value);
    meter.setAttribute('data-s', String(s));
    if (meterLabel) { meterLabel.textContent = (LABELS[AF.lang()] || LABELS.en)[s]; }
  }
  if (pw) { pw.addEventListener('input', paintMeter); paintMeter(); }

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
