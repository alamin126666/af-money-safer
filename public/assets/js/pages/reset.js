AF.ready(function () {
  var f = AF.$('#f'), btn = AF.$('#submit');

  /* Password-strength meter — uses .pw-meter[data-s] + .pw-meter-label if present,
     otherwise injects them right after the new-password field. Score 0-4. */
  var pw = f ? f.querySelector('input[name="newPassword"]') : null;
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
