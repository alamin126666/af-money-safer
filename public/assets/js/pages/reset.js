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
  var LABELS = { bn: ['খুব দুর্বল', 'দুর্বল', 'মোটামুটি', 'ভালো', 'শক্তিশালী'], en: ['Very weak', 'Weak', 'Fair', 'Good', 'Strong'] };
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
    if (meterLabel) { meterLabel.textContent = pw.value ? (LABELS[AF.lang()] || LABELS.en)[s] : ''; }
  }
  /* Confirm-password match — live text under the confirm field. */
  var cf = f ? f.querySelector('input[name="confirmPassword"]') : null;
  var match = document.querySelector('.pw-match');
  if (cf && !match) {
    match = document.createElement('small');
    match.className = 'pw-match';
    var anchor = cf.closest('.input');
    if (anchor && anchor.parentNode) { anchor.parentNode.insertBefore(match, anchor.nextSibling); }
  }
  var MATCH = { bn: { ok: 'পাসওয়ার্ড মিলেছে', bad: 'পাসওয়ার্ড মিলছে না' }, en: { ok: 'Passwords match', bad: 'Passwords do not match' } };
  function paintMatch() {
    if (!match || !cf) { return; }
    if (!cf.value) { match.textContent = ''; match.className = 'pw-match'; return; }
    var ok = pw && cf.value === pw.value;
    var t = MATCH[AF.lang()] || MATCH.en;
    match.textContent = ok ? t.ok : t.bad;
    match.className = 'pw-match ' + (ok ? 'ok' : 'bad');
  }
  if (pw) { pw.addEventListener('input', function () { paintMeter(); paintMatch(); }); paintMeter(); paintMatch(); }
  if (cf) { cf.addEventListener('input', paintMatch); }

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
