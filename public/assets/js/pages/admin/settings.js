/* Admin · Settings: limits, fees, maintenance mode. */
AF.ready(function () {
  var $ = AF.$, E = AF.esc;
  AF.api('admin/settings-get.php').then(function (j) {
    if (!j.success) { return; } var s = j.data.settings;
    function f(n, l) { return '<label class="field"><span>' + l + '</span><div class="input"><input name="' + n + '" value="' + E(s[n]) + '" inputmode="decimal"></div></label>'; }
    $('#sf').innerHTML = '<h3>Limits</h3>' + f('minDeposit', 'Minimum deposit (BDT)') + f('maxDeposit', 'Maximum deposit (BDT)') + f('minWithdraw', 'Minimum withdraw (BDT, at least 100)') + f('maxWithdraw', 'Maximum withdraw (BDT)') +
      '<h3>Fees</h3>' + f('depositFeePercent', 'Deposit fee (%)') + f('withdrawFeePercent', 'Withdraw fee (%)') + '<div class="summary" id="feePrev"></div>' +
      '<h3>Maintenance</h3><label class="field"><span>Maintenance mode</span><div class="input"><input type="checkbox" name="maintenance" ' + (s.maintenance ? 'checked' : '') + ' style="flex:none;width:22px;height:22px"></div></label><button class="btn btn-primary btn-block">Save settings</button>';
    var sf = $('#sf');
    (function preview() {
      var dp = parseFloat(sf.depositFeePercent.value) || 0, wp = parseFloat(sf.withdrawFeePercent.value) || 0;
      var depFee = Math.ceil(1000 * 100 * dp / 100 - 1e-9), wdFee = Math.ceil(100 * 100 * wp / 100 - 1e-9);
      var el = document.getElementById('feePrev'); if (!el) { return; }
      el.innerHTML = '<div class="row"><span class="muted">Deposit ৳1,000 → fee</span><b>৳' + AF.money(depFee) + ' · user gets ৳' + AF.money(100000 - depFee) + '</b></div>'
        + '<div class="row"><span class="muted">Withdraw ৳100 → fee</span><b>৳' + AF.money(wdFee) + ' · total debit ৳' + AF.money(10000 + wdFee) + '</b></div>';
    })();
    sf.addEventListener('input', function () { var dp = parseFloat(sf.depositFeePercent.value) || 0, wp = parseFloat(sf.withdrawFeePercent.value) || 0;
      var depFee = Math.ceil(1000 * 100 * dp / 100 - 1e-9), wdFee = Math.ceil(100 * 100 * wp / 100 - 1e-9);
      var el = document.getElementById('feePrev'); if (!el) { return; }
      el.innerHTML = '<div class="row"><span class="muted">Deposit ৳1,000 → fee</span><b>৳' + AF.money(depFee) + ' · user gets ৳' + AF.money(100000 - depFee) + '</b></div>'
        + '<div class="row"><span class="muted">Withdraw ৳100 → fee</span><b>৳' + AF.money(wdFee) + ' · total debit ৳' + AF.money(10000 + wdFee) + '</b></div>'; });
    sf.addEventListener('submit', function (e) { e.preventDefault(); var d = AF.form(e.target); d.maintenance = e.target.maintenance.checked; AF.api('admin/settings-update.php', d, { ok: true }); });
  });
});
