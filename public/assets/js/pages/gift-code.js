AF.ready(function () {
  var $ = AF.$, rows = $('#giftRows'), empty = $('#giftEmpty'), form = $('#giftForm'), succ = $('#giftSuccess');
  function draw(items) {
    if (!rows) { return; }
    if (!items.length) { rows.innerHTML = ''; if (empty) { empty.hidden = false; } return; }
    if (empty) { empty.hidden = true; }
    rows.innerHTML = items.map(function (r) {
      return '<tr><td><b>' + AF.esc(r.code) + '</b></td><td>৳' + AF.esc(AF.money(r.amount)) + '</td><td>' + AF.esc(AF.date(r.createdAtMs)) + ' · ' + AF.esc(AF.time(r.createdAtMs)) + '</td></tr>';
    }).join('');
  }
  function load() {
    AF.api('promo/redemptions.php', undefined, { quiet: true }).then(function (j) {
      if (!j.success) { return; }
      draw(j.data.items || []);
    });
  }
  load();
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var d = AF.form(form);
      var code = (d.code || '').trim().toUpperCase();
      if (!code) { AF.toast(AF.t('gift_invalid'), 'error'); return; }
      var b = form.querySelector('button'); AF.busy(b, true);
      AF.api('promo/redeem.php', { code: code }, { ok: true }).then(function (j) {
        AF.busy(b, false);
        if (!j.success) { return; }
        form.reset();
        if (succ) {
          succ.hidden = false;
          var ic = $('#giftSuccessIc'); if (ic) { ic.innerHTML = AF.animCheck(52); }
          var amt = $('#giftSuccessAmt'); if (amt) { amt.textContent = '৳' + AF.money(j.data.amount) + ' · ' + AF.t('gift_success'); }
          var bal = $('#giftSuccessBal'); if (bal && j.data.balance != null) { bal.textContent = AF.t('now_balance') + ': ৳' + AF.money(j.data.balance); }
        }
        load();
      });
    });
  }
});
