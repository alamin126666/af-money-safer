/* Admin · Settings: limits, fees, maintenance mode. */
AF.ready(function () {
  var $ = AF.$, E = AF.esc;
  AF.api('admin/settings-get.php').then(function (j) {
    if (!j.success) { return; } var s = j.data.settings;
    function f(n, l) { return '<label class="field"><span>' + l + '</span><div class="input"><input name="' + n + '" value="' + E(s[n]) + '" inputmode="decimal"></div></label>'; }
    $('#sf').innerHTML = f('minDeposit', 'Minimum deposit (BDT)') + f('maxDeposit', 'Maximum deposit (BDT)') + f('minWithdraw', 'Minimum withdraw (BDT, at least 100)') + f('maxWithdraw', 'Maximum withdraw (BDT)') +
      f('depositFeePercent', 'Deposit fee (%)') + f('withdrawFeePercent', 'Withdraw fee (%)') +
      '<label class="field"><span>Maintenance mode</span><div class="input"><input type="checkbox" name="maintenance" ' + (s.maintenance ? 'checked' : '') + ' style="flex:none;width:22px;height:22px"></div></label><button class="btn btn-primary btn-block">Save settings</button>';
    $('#sf').addEventListener('submit', function (e) { e.preventDefault(); var d = AF.form(e.target); d.maintenance = e.target.maintenance.checked; AF.api('admin/settings-update.php', d, { ok: true }); });
  });
});
