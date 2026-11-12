AF.ready(function () {
  var $ = AF.$, form = $('#snForm'), list = $('#savedNumList'), empty = $('#savedNumEmpty'),
      submitLbl = $('#snSubmitLbl'), cancelBtn = $('#snCancel'),
      method = 'bKash', editingId = null, items = [];
  if (!form || !list) { return; }
  var wno = form.querySelector('[name=walletNo]'), hn = form.querySelector('[name=holderName]');
  wno.addEventListener('input', function () { wno.value = wno.value.replace(/[^\d+]/g, '').slice(0, 14); });

  function maskNo(n) { return n ? n.slice(0, 3) + '****' + n.slice(-3) : ''; }

  function syncSubmitLbl() {
    if (submitLbl) { submitLbl.textContent = AF.t(editingId ? 'saved_num_update' : 'save'); }
  }
  function setMode(id) {
    editingId = id || null;
    if (cancelBtn) { cancelBtn.hidden = !editingId; }
    syncSubmitLbl();
  }
  document.addEventListener('af:lang', syncSubmitLbl);
  syncSubmitLbl(); /* JS owns the label (Save/Update); not a data-i18n span */

  function draw() {
    list.innerHTML = '';
    if (!items.length) { if (empty) { empty.hidden = false; } return; }
    if (empty) { empty.hidden = true; }
    items.forEach(function (it) {
      var el = document.createElement('div'); el.className = 'saved-num'; el.style.cursor = 'default';
      el.innerHTML = '<div class="tx-ic">' + AF.lotIcon('wallet', 18) + '</div>'
        + '<div class="tx-main"><b>' + AF.esc(it.name) + '</b><small>' + AF.esc(it.method) + ' · ' + AF.esc(maskNo(it.walletNo)) + '</small></div>'
        + '<button class="btn btn-ghost btn-sm" type="button" data-edit="' + AF.esc(it.id) + '">' + AF.esc(AF.t('edit')) + '</button>'
        + '<button class="btn btn-danger btn-sm" type="button" data-del="' + AF.esc(it.id) + '">' + AF.esc(AF.t('remove')) + '</button>';
      list.appendChild(el);
    });
    AF.apply(list);
  }
  function load() {
    AF.api('withdraw/saved-numbers.php', undefined, { quiet: true }).then(function (j) {
      if (!j.success) { return; }
      items = j.data.items || [];
      draw();
    });
  }
  load();

  $('#snMethods').addEventListener('click', function (e) {
    var b = e.target.closest('.method'); if (!b) { return; }
    method = b.getAttribute('data-m');
    [].forEach.call(document.querySelectorAll('#snMethods .method'), function (x) { x.classList.toggle('sel', x === b); });
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var d = AF.form(form);
    if (!d.walletNo || !d.holderName) { AF.toast(AF.t('saved_num_fill_first'), 'error'); return; }
    var body = { name: d.holderName, method: method, walletNo: d.walletNo };
    if (editingId) { body.id = editingId; }
    var b = form.querySelector('#snSubmit'); AF.busy(b, true);
    AF.api('withdraw/saved-numbers.php', body, { ok: true }).then(function (j) {
      AF.busy(b, false);
      if (!j.success) { return; }
      form.reset();
      setMode(null);
      var sel = document.querySelector('#snMethods .method[data-m="bKash"]');
      if (sel) { sel.click(); }
      load();
    });
  });
  if (cancelBtn) {
    cancelBtn.addEventListener('click', function () {
      form.reset();
      setMode(null);
      var sel = document.querySelector('#snMethods .method[data-m="bKash"]');
      if (sel) { sel.click(); }
    });
  }

  list.addEventListener('click', function (e) {
    var eb = e.target.closest('[data-edit]');
    if (eb) {
      var it = null;
      items.forEach(function (x) { if (x.id === eb.getAttribute('data-edit')) { it = x; } });
      if (!it) { return; }
      var mb = document.querySelector('#snMethods .method[data-m="' + it.method + '"]');
      if (mb) { mb.click(); }
      hn.value = it.name;
      wno.value = it.walletNo;
      setMode(it.id);
      form.scrollIntoView({ behavior: 'smooth', block: 'start' });
      return;
    }
    var db = e.target.closest('[data-del]');
    if (!db) { return; }
    var id = db.getAttribute('data-del'), it2 = null;
    items.forEach(function (x) { if (x.id === id) { it2 = x; } });
    var m = AF.modal({
      title: AF.t('saved_num_remove_q'),
      html: '<p class="muted">' + AF.esc(it2 ? it2.name + ' · ' + it2.method + ' · ' + maskNo(it2.walletNo) : '') + '</p>'
        + '<p style="display:flex;gap:10px;margin-top:14px"><button class="btn btn-danger" type="button" data-yes style="flex:1">' + AF.esc(AF.t('remove')) + '</button>'
        + '<button class="btn btn-ghost" type="button" data-no style="flex:1">' + AF.esc(AF.t('cancel')) + '</button></p>'
    });
    m.el.querySelector('[data-no]').addEventListener('click', function () { m.close(); });
    m.el.querySelector('[data-yes]').addEventListener('click', function () {
      m.close();
      AF.api('withdraw/saved-numbers.php', { id: id }, { method: 'DELETE', ok: true }).then(function (j) {
        if (!j.success) { return; }
        if (editingId === id) { form.reset(); setMode(null); }
        load();
      });
    });
  });
});
