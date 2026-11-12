/* Admin · Settings: limits, fees, maintenance mode. */
AF.ready(function () {
  var $ = AF.$, E = AF.esc;
  AF.api('admin/settings-get.php').then(function (j) {
    if (!j.success) { return; } var s = j.data.settings;
    function f(n, l) { return '<label class="field"><span>' + l + '</span><div class="input"><input name="' + n + '" value="' + E(s[n]) + '" inputmode="decimal"></div></label>'; }
    function mins() {
      var opts = [15, 30, 60, 120, 240], rem = s.maintenanceUntilMs ? Math.max(1, Math.round((s.maintenanceUntilMs - Date.now()) / 60000)) : 30, sel = 30;
      for (var i = 0; i < opts.length; i++) { sel = opts[i]; if (opts[i] >= rem) { break; } }
      return opts.map(function (o) { return '<option value="' + o + '"' + (o === sel ? ' selected' : '') + '>' + o + ' minutes</option>'; }).join('');
    }
    function feeHtml(dp, wp) {
      var depFee = Math.ceil(1000 * 100 * dp / 100 - 1e-9), wdFee = Math.ceil(100 * 100 * wp / 100 - 1e-9);
      return '<div class="row"><span class="muted">Deposit ৳1,000 → fee</span><b>৳' + AF.money(depFee) + ' · user gets ৳' + AF.money(100000 - depFee) + '</b></div>'
        + '<div class="row"><span class="muted">Withdraw ৳100 → fee</span><b>৳' + AF.money(wdFee) + ' · total debit ৳' + AF.money(10000 + wdFee) + '</b></div>';
    }
    $('#sf').innerHTML = '<section class="card"><h3 style="margin-top:0">Limits</h3>' + f('minDeposit', 'Minimum deposit (BDT)') + f('maxDeposit', 'Maximum deposit (BDT)') + f('minWithdraw', 'Minimum withdraw (BDT, at least 100)') + f('maxWithdraw', 'Maximum withdraw (BDT)') +
      '<h3>Fees</h3>' + f('depositFeePercent', 'Deposit fee (%)') + f('withdrawFeePercent', 'Withdraw fee (%)') + '<div class="summary" id="feePrev"></div></section>' +
      '<section class="card maint-card"><div class="maint-head"><span class="maint-ic">' + AF.icon('settings', 22) + '</span><div class="maint-title"><h3>Maintenance mode</h3><p class="muted">When on, users are sent to a maintenance page with the reason and a countdown. Admins are not affected.</p></div>' +
      '<label class="m-toggle" aria-label="Maintenance mode"><input type="checkbox" name="maintenance" ' + (s.maintenance ? 'checked' : '') + '><span class="m-track"><span class="m-knob"></span></span></label></div>' +
      '<div class="maint-live" id="maintLive" hidden></div>' +
      '<div class="maint-fields" id="maintFields"><label class="field"><span>Reason (shown to users)</span><div class="input"><input name="maintenanceReason" value="' + E(s.maintenanceReason || '') + '" maxlength="200" placeholder="e.g. Server upgrade in progress"></div></label>' +
      '<label class="field"><span>How long</span><div class="input select-modern"><select name="maintenanceMinutes">' + mins() + '</select><span class="select-chev">' + AF.icon('chevron', 18) + '</span></div></label></div></section>' +
      '<button class="btn btn-primary btn-block">Save settings</button>';
    var sf = $('#sf');
    (function preview() {
      var el = document.getElementById('feePrev'); if (!el) { return; }
      el.innerHTML = feeHtml(parseFloat(sf.depositFeePercent.value) || 0, parseFloat(sf.withdrawFeePercent.value) || 0);
    })();
    sf.addEventListener('input', function () {
      var el = document.getElementById('feePrev'); if (!el) { return; }
      el.innerHTML = feeHtml(parseFloat(sf.depositFeePercent.value) || 0, parseFloat(sf.withdrawFeePercent.value) || 0);
    });
    var tg = sf.maintenance, fields = document.getElementById('maintFields');
    function syncFields() { fields.style.display = tg.checked ? '' : 'none'; }
    tg.addEventListener('change', syncFields); syncFields();
    (function live() {
      var el = document.getElementById('maintLive'); if (!el) { return; }
      function tick() {
        if (!s.maintenance || !s.maintenanceUntilMs) { el.hidden = true; return; }
        var ms = s.maintenanceUntilMs - Date.now();
        if (ms <= 0) { el.hidden = true; return; }
        var sec = Math.floor(ms / 1000), h = Math.floor(sec / 3600), m = Math.floor(sec % 3600 / 60), ss = sec % 60;
        el.hidden = false;
        el.innerHTML = '<span class="maint-dot"></span><div><b>Live now — ' + E(s.maintenanceReason || 'Maintenance') + '</b><small>Ends in ' + (h ? h + 'h ' : '') + (m < 10 ? '0' : '') + m + 'm ' + (ss < 10 ? '0' : '') + ss + 's · ' + new Date(s.maintenanceUntilMs).toLocaleString() + '</small></div>';
      }
      tick(); setInterval(tick, 1000);
    })();
    sf.addEventListener('submit', function (e) { e.preventDefault(); var d = AF.form(e.target); d.maintenance = e.target.maintenance.checked; AF.api('admin/settings-update.php', d, { ok: true }); });
  });
});
