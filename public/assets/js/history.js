/* Deposit / withdraw history with filters, tabs, "load more" and detail modal. Data: /api/history/get.php */
(function () {
  'use strict';
  var $ = AF.$;
  window.AFHistory = {
    mount: function (root) {
      var full = root.getAttribute('data-full') === '1', f = { type: '', startDate: '', endDate: '', method: '', status: '' }, cursor = null, loading = false, shown = [];
      function sel(name, opts) { return '<select name="' + name + '"><option value="">' + AF.esc(AF.t(name === 'method' ? 'method' : name === 'status' ? 'status' : 'type')) + ': ' + AF.esc(AF.t('all')) + '</option>' + opts.map(function (o) { return '<option value="' + o[0] + '">' + AF.esc(AF.t(o[1])) + '</option>'; }).join('') + '</select>'; }
      function shell() {
        root.innerHTML = '<div class="seg-tabs" id="hTabs"><button data-v="" class="on">' + AF.esc(AF.t('all')) + '</button><button data-v="deposit">' + AF.esc(AF.t('t_deposit')) + '</button><button data-v="withdraw">' + AF.esc(AF.t('t_withdraw')) + '</button></div>' +
          '<button class="link-btn" id="hFilterBtn" type="button">' + AF.esc(AF.t('filters')) + '</button>' +
          '<form class="filters" id="hFilters" hidden style="margin-top:10px"><label>' + AF.esc(AF.t('start_date')) + '<input type="date" name="startDate"></label><label>' + AF.esc(AF.t('end_date')) + '<input type="date" name="endDate"></label>' +
          sel('method', [['bKash', 'm_bKash'], ['Nagad', 'm_Nagad']]) + sel('status', [['pending', 's_pending'], ['processing', 's_processing'], ['successful', 's_successful'], ['failed', 's_failed'], ['cancelled', 's_cancelled']]) +
          '<button class="btn btn-primary" type="submit">' + AF.esc(AF.t('apply')) + '</button><button class="btn btn-ghost" type="reset">' + AF.esc(AF.t('reset')) + '</button></form>' +
          '<div id="hList" style="margin-top:8px"></div><div class="empty" id="hEmpty" hidden>' + AF.esc(AF.t('no_data')) + '</div><button class="btn btn-ghost btn-block" id="hMore" type="button" hidden>' + AF.esc(AF.t('load_more')) + '</button>';
      }
      function row(t) {
        var el = document.createElement('div'); el.className = 'tx'; el.setAttribute('data-id', t.id);
        var sign = t.type === 'deposit' ? '+' : '−';
        el.innerHTML = '<div class="tx-ic ' + t.type + '">' + AF.icon(t.type) + '</div><div class="tx-main"><b>' + AF.esc(AF.t('t_' + t.type)) + ' · ' + AF.esc(t.method) + '</b><small>' + AF.esc(AF.date(t.createdAtMs)) + ' · ' + AF.esc(AF.time(t.createdAtMs)) + '</small></div>' +
          '<div class="tx-amt">' + sign + '৳' + AF.esc(AF.money(t.amount)) + '<br><span class="pill ' + AF.esc(t.status) + '">' + AF.esc(AF.t('s_' + t.status)) + '</span></div>';
        el.addEventListener('click', function () { detail(t); });
        return el;
      }
      function detail(t) {
        var rows = [['tx_id', t.id, 1], ['order_id', t.orderId, 1], ['type', AF.t('t_' + t.type)], ['method', t.method], ['amount', '৳' + AF.money(t.amount)]];
        if (t.fee) { rows.push(['fee', '৳' + AF.money(t.fee)]); }
        rows.push(['status', AF.t('s_' + t.status)], ['date', AF.date(t.createdAtMs)], ['time', AF.time(t.createdAtMs)]);
        if (t.walletNo) { rows.push(['wallet', t.walletNo]); } if (t.holderName) { rows.push(['holder', t.holderName]); } if (t.flowId) { rows.push(['reference', t.flowId]); }
        var m = AF.modal({ title: AF.t('tx_details'), html: rows.map(function (r) { return '<div class="kv"><span class="muted">' + AF.esc(AF.t(r[0])) + '</span><b style="word-break:break-all;text-align:right">' + AF.esc(r[1]) + (r[2] ? ' <button type="button" class="link-btn" data-c="' + AF.esc(r[1]) + '" aria-label="Copy">' + AF.icon('copy', 16) + '</button>' : '') + '</b></div>'; }).join('') });
        m.body.addEventListener('click', function (e) { var c = e.target.closest('[data-c]'); if (c) { AF.copy(c.getAttribute('data-c')); } });
      }
      function load(reset) {
        if (loading) { return; } loading = true;
        if (reset) { cursor = null; $('#hList', root).innerHTML = ''; }
        var body = Object.assign({}, f, { cursor: cursor, limit: full ? 15 : 8 });
        AF.api('history/get.php', body).then(function (j) {
          loading = false; if (!j.success) { return; }
          var list = $('#hList', root); j.data.items.forEach(function (t) { list.appendChild(row(t)); });
          cursor = j.data.next; $('#hMore', root).hidden = !cursor; $('#hEmpty', root).hidden = list.children.length > 0;
        });
      }
      shell(); load(true);
      root.addEventListener('click', function (e) {
        var tab = e.target.closest('#hTabs button');
        if (tab) { f.type = tab.getAttribute('data-v'); [].forEach.call(root.querySelectorAll('#hTabs button'), function (b) { b.classList.toggle('on', b === tab); }); load(true); }
        if (e.target.closest('#hFilterBtn')) { var fl = $('#hFilters', root); fl.hidden = !fl.hidden; }
        if (e.target.closest('#hMore')) { load(false); }
      });
      $('#hFilters', root).addEventListener('submit', function (e) { e.preventDefault(); var d = AF.form(e.target); f.startDate = d.startDate; f.endDate = d.endDate; f.method = d.method; f.status = d.status; load(true); });
      $('#hFilters', root).addEventListener('reset', function () { setTimeout(function () { f.startDate = f.endDate = f.method = f.status = ''; load(true); }, 0); });
      document.addEventListener('af:lang', function () { shell(); load(true); });
    }
  };
})();
