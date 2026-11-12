AF.ready(function () {
  var $ = AF.$, p = null;
  function paint() {
    if (!p) { return; }
    var on = !!p.totpEnabled;
    var ic = $('#secBigIc');
    if (ic) {
      var icn = on ? 'shield-check' : 'shield-off';
      if (ic.getAttribute('data-icon') !== icn) { ic.setAttribute('data-icon', icn); }
      ic.classList.toggle('sec-on', on); ic.classList.toggle('sec-off', !on);
      AF.lottieFill(ic);
    }
    var st = $('#secBigState'); if (st) { st.textContent = AF.t(on ? 'twofa_on' : 'twofa_off'); }
    var hint = $('#secBigHint'); if (hint) { hint.textContent = AF.t(on ? 'sec_hint_on' : 'sec_hint_off'); }
    var lb = $('#secBtnLabel'); if (lb) { lb.textContent = AF.t(on ? 'twofa_disable' : 'twofa_enable'); }
  }
  function load() { return AF.api('profile/get.php').then(function (j) { if (j.success) { p = j.data; paint(); } }); }
  load();
  document.addEventListener('af:lang', paint);

  function field(label, name, type, extra) {
    return '<label class="field"><span>' + AF.esc(label) + '</span><div class="input"><input name="' + name + '" type="' + (type || 'text') + '" ' + (extra || '') + '></div><small class="err" data-err="' + name + '"></small></label>';
  }
  function simple(title, html, submit) {
    var m = AF.modal({ title: title, html: '<form novalidate>' + html + '<button class="btn btn-primary btn-block" type="submit">' + AF.esc(AF.t('save')) + '</button></form>' });
    var f = $('form', m.el); $('input', f).focus();
    f.addEventListener('submit', function (e) { e.preventDefault(); AF.fieldErrors(f, null); var b = $('button', f); AF.busy(b, true); submit(AF.form(f), m, f).then(function () { AF.busy(b, false); }); });
    return m;
  }

  $('#secBtn').addEventListener('click', function () {
    if (!p) { return; }
    if (p.totpEnabled) {
      // Disable: current password + a code from the Authenticator app.
      simple(AF.t('twofa_disable'),
        field(AF.t('current_password'), 'password', 'password', 'autocomplete="current-password"')
        + field(AF.t('totp_code'), 'code', 'text', 'inputmode="numeric" maxlength="6" autocomplete="one-time-code"'),
        function (d, m, f) {
          return AF.api('profile/totp-disable.php', { password: d.password, code: d.code }).then(function (j) {
            if (j.success) { m.close(); AF.toast(j.message, 'success'); load(); }
            else if (j.data && j.data.fields) { AF.fieldErrors(f, j.data.fields); }
          });
        });
      return;
    }
    // Enable, step 1: current password -> the server makes a secret.
    simple(AF.t('twofa_enable'), field(AF.t('current_password'), 'password', 'password', 'autocomplete="current-password"'), function (d, m, f) {
      return AF.api('profile/totp-setup.php', { password: d.password }).then(function (j) {
        if (!j.success) { return; }
        m.close();
        // Step 2: show the secret key + how to add it by hand, then a Next
        // button opens the verify step (enter the app's 6-digit code).
        var sec = j.data, pad;
        var m2 = AF.modal({ title: AF.t('twofa_setup_title'), html:
          '<div id="totpSetupPart">'
          + '<p class="muted">' + AF.esc(AF.t('twofa_setup_note')) + '</p>'
          + '<label class="field"><span>' + AF.esc(AF.t('totp_secret')) + '</span><div class="input"><input value="' + AF.esc(sec.secret) + '" readonly></div></label>'
          + '<button class="btn btn-ghost btn-block" type="button" id="totpCopySec">' + AF.esc(AF.t('copy_secret')) + '</button>'
          + '<ol class="muted" style="margin:14px 0 0;padding-left:20px">'
          + '<li>' + AF.esc(AF.t('twofa_step1')) + '</li>'
          + '<li>' + AF.esc(AF.t('twofa_step2')) + '</li>'
          + '<li>' + AF.esc(AF.t('twofa_step3')) + '</li>'
          + '<li>' + AF.esc(AF.t('twofa_step4')) + '</li></ol>'
          + '<button class="btn btn-primary btn-block" type="button" id="totpNext" style="margin-top:14px">' + AF.esc(AF.t('next')) + '</button>'
          + '</div>'
          + '<div id="totpVerifyPart" hidden>'
          + '<p class="muted">' + AF.esc(AF.t('totp_enter_code')) + '</p>'
          + '<div class="otp-boxes" id="mb"></div><div class="numpad" id="mp"></div>'
          + '<button class="btn btn-primary btn-block" id="mc" type="button">' + AF.esc(AF.t('twofa_verify_btn')) + '</button>'
          + '</div>',
          onClose: function () { if (pad) { pad.destroy(); } } });
        $('#totpCopySec', m2.el).addEventListener('click', function () { AF.copy(sec.secret); });
        $('#totpNext', m2.el).addEventListener('click', function () {
          $('#totpSetupPart', m2.el).hidden = true;
          $('#totpVerifyPart', m2.el).hidden = false;
          pad = AF.otpPad({ boxes: $('#mb', m2.el), pad: $('#mp', m2.el), length: 6, onComplete: function () {} });
        });
        $('#mc', m2.el).addEventListener('click', function () {
          if (pad.get().length !== 6) { AF.toast(AF.t('code_incomplete'), 'error'); return; }
          var b = $('#mc', m2.el); AF.busy(b, true);
          AF.api('profile/totp-verify.php', { code: pad.get() }).then(function (r) {
            AF.busy(b, false);
            if (r.success) { m2.close(); AF.toast(r.message, 'success'); load(); }
            else { pad.shake(); pad.clear(); }
          });
        });
      });
    });
  });
});
