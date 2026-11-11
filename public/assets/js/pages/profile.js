AF.ready(function () {
  var $ = AF.$, p = null;
  function draw() {
    if (!p) { return; }
    var n = $('#pName'); n.classList.remove('skeleton-text'); n.textContent = p.fullName; $('#pEmail').textContent = p.email;
    $('#adminLink').hidden = !p.isAdmin;
    var aBtn = $('#adminLinkBtn'); if (aBtn) { aBtn.hidden = !p.isAdmin; }
    $('#pId').textContent = p.accountId; $('#pDate').textContent = AF.date(p.registeredAtMs);
    $('#avatar').innerHTML = AF.avatar(p.avatar, 96);
    var a = $('#topAvatar'); if (a) { a.removeAttribute('data-icon'); a.innerHTML = AF.avatar(p.avatar, 40); }
    [].forEach.call(document.querySelectorAll('#segLang button'), function (b) { b.classList.toggle('on', b.getAttribute('data-v') === AF.lang()); });
    [].forEach.call(document.querySelectorAll('#segTheme button'), function (b) { b.classList.toggle('on', b.getAttribute('data-v') === AF.theme()); });
  }
  function load() { return AF.api('profile/get.php').then(function (j) { if (j.success) { p = j.data; draw(); } }); }
  load();
  document.addEventListener('af:lang', draw); document.addEventListener('af:theme', draw);

  $('#segLang').addEventListener('click', function (e) { var b = e.target.closest('button'); if (b) { AF.setLang(b.getAttribute('data-v')); } });
  $('#segTheme').addEventListener('click', function (e) { var b = e.target.closest('button'); if (b) { AF.setTheme(b.getAttribute('data-v')); } });
  $('#logout').addEventListener('click', function () { AF.api('auth/logout.php', {}).then(function () { try { sessionStorage.clear(); } catch (e) {} AF.go('/login.php'); }); });

  function field(label, name, type, extra) {
    return '<label class="field"><span>' + AF.esc(label) + '</span><div class="input"><input name="' + name + '" type="' + (type || 'text') + '" ' + (extra || '') + '></div><small class="err" data-err="' + name + '"></small></label>';
  }
  function simple(title, html, submit) {
    var m = AF.modal({ title: title, html: '<form novalidate>' + html + '<button class="btn btn-primary btn-block" type="submit">' + AF.esc(AF.t('save')) + '</button></form>' });
    var f = $('form', m.el); $('input', f).focus();
    f.addEventListener('submit', function (e) { e.preventDefault(); AF.fieldErrors(f, null); var b = $('button', f); AF.busy(b, true); submit(AF.form(f), m, f).then(function () { AF.busy(b, false); }); });
    return m;
  }

  /** Reusable 2nd step: 6-digit code (custom pad) + optional extra fields. */
  function codeStep(title, info, extraHtml, send, done) {
    var pad, m = AF.modal({ title: title, html: '<p class="muted">' + AF.esc(AF.t('otp_desc', { email: info.email })) + '</p>' + (extraHtml || '') +
      '<div class="otp-boxes" id="mb"></div><div class="numpad" id="mp"></div><button class="btn btn-primary btn-block" id="mc" type="button">' + AF.esc(AF.t('continue')) + '</button>', onClose: function () { pad && pad.destroy(); } });
    pad = AF.otpPad({ boxes: $('#mb', m.el), pad: $('#mp', m.el), length: 6, onComplete: function () {} });
    $('#mc', m.el).addEventListener('click', function () {
      if (pad.get().length !== 6) { AF.toast(AF.t('code_incomplete'), 'error'); return; }
      var b = $('#mc', m.el); AF.busy(b, true);
      send(pad.get(), m.el).then(function (j) { AF.busy(b, false); if (j.success) { m.close(); done(j); } else { pad.shake(); pad.clear(); if (j.data && j.data.fields) { AF.fieldErrors(m.el, j.data.fields); } } });
    });
  }

  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-act]'); if (!b) { return; } var act = b.getAttribute('data-act');
    if (act === 'name') {
      simple(AF.t('change_name'), field(AF.t('full_name'), 'fullName', 'text', 'maxlength="60" value="' + AF.esc(p.fullName) + '"'), function (d, m, f) {
        return AF.api('profile/update-name.php', { fullName: d.fullName }).then(function (j) { if (j.success) { m.close(); AF.toast(j.message, 'success'); load(); } else if (j.data.fields) { AF.fieldErrors(f, j.data.fields); } });
      });
    }
    if (act === 'email') {
      simple(AF.t('change_email'), field(AF.t('new_email'), 'newEmail', 'email', 'autocomplete="off"') + field(AF.t('current_password'), 'password', 'password', 'autocomplete="current-password"'), function (d, m, f) {
        return AF.api('profile/update-email.php', { step: 'request', newEmail: d.newEmail, password: d.password }).then(function (j) {
          if (!j.success) { if (j.data.fields) { AF.fieldErrors(f, j.data.fields); } return; }
          m.close(); AF.toast(j.message, 'success');
          codeStep(AF.t('change_email'), j.data, '', function (code) { return AF.api('profile/update-email.php', { step: 'confirm', code: code }); }, function (r) { AF.toast(r.message, 'success'); load(); });
        });
      });
    }
    if (act === 'password') {
      simple(AF.t('change_password'), field(AF.t('current_password'), 'currentPassword', 'password', 'autocomplete="current-password"'), function (d, m) {
        return AF.api('profile/update-password.php', { step: 'request', currentPassword: d.currentPassword }).then(function (j) {
          if (!j.success) { return; } m.close(); AF.toast(j.message, 'success');
          var extra = field(AF.t('new_password'), 'newPassword', 'password', 'autocomplete="new-password"') + field(AF.t('confirm_password'), 'confirmPassword', 'password', 'autocomplete="new-password"');
          codeStep(AF.t('change_password'), j.data, extra, function (code, el) { var x = AF.form(el); return AF.api('profile/update-password.php', { step: 'confirm', code: code, newPassword: x.newPassword, confirmPassword: x.confirmPassword }); }, function (r) { AF.toast(r.message, 'success'); });
        });
      });
    }
    if (act === 'photo') { $('#photoFile').click(); }
    if (act === 'avatar') {
      var grid = '<div class="avatars">' + ['default', 'p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'].map(function (k) { return '<button type="button" data-k="' + k + '" class="' + (p.avatar && p.avatar.type === 'preset' && p.avatar.key === k ? 'sel' : '') + '">' + AF.avatar({ type: 'preset', key: k }, 64) + '</button>'; }).join('') + '</div>';
      var m = AF.modal({ title: AF.t('choose_avatar'), html: grid });
      m.body.addEventListener('click', function (ev) { var x = ev.target.closest('[data-k]'); if (!x) { return; }
        AF.api('profile/set-avatar.php', { key: x.getAttribute('data-k') }).then(function (j) { if (j.success) { m.close(); AF.toast(j.message, 'success'); load(); } }); });
    }
  });

  $('#photoFile').addEventListener('change', function (e) {
    var file = e.target.files[0]; e.target.value = ''; if (!file) { return; }
    if (file.size > 2 * 1024 * 1024) { AF.toast(AF.lang() === 'bn' ? 'ছবির আকার ২ MB-এর বেশি।' : 'The image is larger than 2 MB.', 'error'); return; }
    var fd = new FormData(); fd.append('photo', file);
    AF.api('profile/upload-photo.php', fd, { method: 'POST' }).then(function (j) { if (j.success) { AF.toast(j.message, 'success'); load(); } });
  });
});
