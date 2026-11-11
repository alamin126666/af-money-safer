/* Deposit / withdraw history — filters, tabs, load-more, detail modal, delete, custom date dialog */
(function () {
  'use strict';
  var $ = AF.$;

  /* ───── Custom date-range picker dialog ───── */
  window.AFDateRange = {
    open: function (startVal, endVal, onDone) {
      var months = AF.lang() === 'bn'
        ? ['জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর']
        : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      var days0 = AF.lang() === 'bn' ? ['রবি','সোম','মঙ্গল','বুধ','বৃহ','শুক্র','শনি'] : ['Su','Mo','Tu','We','Th','Fr','Sa'];

      var today = new Date(), ty = today.getFullYear(), tm = today.getMonth(), td = today.getDate();
      var view  = { y: ty, m: tm };
      var sel   = { start: startVal || '', end: endVal || '' };

      function ymd(d) { return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }
      function parseD(s) { return s ? new Date(s + 'T00:00:00') : null; }

      function renderCal(el) {
        var y = view.y, mo = view.m;
        var first = new Date(y, mo, 1).getDay();
        var last  = new Date(y, mo + 1, 0).getDate();
        var sD = parseD(sel.start), eD = parseD(sel.end);
        var html = '<div class="drc-nav"><button class="drc-nav-btn" id="drcPrev">&#8249;</button>'
          + '<span class="drc-month">' + months[mo] + ' ' + y + '</span>'
          + '<button class="drc-nav-btn" id="drcNext">&#8250;</button></div>'
          + '<div class="drc-grid"><div class="drc-heads">'
          + days0.map(function(d){ return '<span>' + d + '</span>'; }).join('') + '</div><div class="drc-days">';
        for (var i = 0; i < first; i++) html += '<span></span>';
        for (var d = 1; d <= last; d++) {
          var dObj = new Date(y, mo, d);
          var ds = ymd(dObj);
          var cls = 'drc-day';
          var isFuture = dObj > today;
          if (isFuture) cls += ' drc-disabled';
          if (ds === sel.start) cls += ' drc-sel drc-sel-s';
          if (ds === sel.end)   cls += ' drc-sel drc-sel-e';
          if (sD && eD && dObj > sD && dObj < eD) cls += ' drc-range';
          if (y === ty && mo === tm && d === td) cls += ' drc-today';
          html += '<button class="' + cls + '" data-d="' + ds + '"' + (isFuture ? ' disabled' : '') + '>' + d + '</button>';
        }
        html += '</div></div>';
        el.innerHTML = html;
      }

      function fmtRange() {
        if (!sel.start && !sel.end) return AF.lang()==='bn' ? 'তারিখ বাছুন' : 'Select range';
        if (sel.start && !sel.end) return sel.start;
        return sel.start + ' → ' + sel.end;
      }

      var presets = AF.lang() === 'bn'
        ? [['আজ',0,0],['৭ দিন',6,0],['১৫ দিন',14,0],['৩০ দিন',29,0]]
        : [['Today',0,0],['7 days',6,0],['15 days',14,0],['30 days',29,0]];

      var preHtml = '<div class="drc-presets">' + presets.map(function(p){
        return '<button class="drc-preset" data-back="'+p[1]+'">' + p[0] + '</button>';
      }).join('') + '</div>';

      var body = '<div class="drc-wrap">'
        + '<div class="drc-info" id="drcInfo">' + AF.esc(fmtRange()) + '</div>'
        + preHtml
        + '<div class="drc-cal" id="drcCal"></div>'
        + '<div class="drc-actions">'
        + '<button class="btn btn-ghost" id="drcClear">' + AF.esc(AF.lang()==='bn'?'মুছুন':'Clear') + '</button>'
        + '<button class="btn btn-primary" id="drcApply">' + AF.esc(AF.lang()==='bn'?'প্রয়োগ করুন':'Apply') + '</button>'
        + '</div></div>';

      var m = AF.modal({ title: AF.lang()==='bn' ? 'তারিখ নির্বাচন' : 'Select Date Range', html: body });
      var cal = $('#drcCal', m.el);
      renderCal(cal);

      m.el.addEventListener('click', function (e) {
        // Nav
        if (e.target.closest('#drcPrev')) { view.m--; if (view.m < 0) { view.m = 11; view.y--; } renderCal(cal); return; }
        if (e.target.closest('#drcNext')) { view.m++; if (view.m > 11) { view.m = 0; view.y++; } renderCal(cal); return; }
        // Day pick
        var dayBtn = e.target.closest('.drc-day'); if (dayBtn) {
          var ds = dayBtn.getAttribute('data-d');
          if (!sel.start || (sel.start && sel.end)) { sel.start = ds; sel.end = ''; }
          else {
            if (ds < sel.start) { sel.end = sel.start; sel.start = ds; }
            else if (ds === sel.start) { sel.end = ''; }
            else { sel.end = ds; }
          }
          $('#drcInfo', m.el).textContent = fmtRange(); renderCal(cal); return;
        }
        // Presets
        var pre = e.target.closest('.drc-preset'); if (pre) {
          var back = parseInt(pre.getAttribute('data-back'), 10);
          var t2 = new Date(); var t1 = new Date(); t1.setDate(t1.getDate() - back);
          sel.start = ymd(t1); sel.end = ymd(t2);
          view.y = t1.getFullYear(); view.m = t1.getMonth();
          $('#drcInfo', m.el).textContent = fmtRange(); renderCal(cal); return;
        }
        // Clear
        if (e.target.closest('#drcClear')) { sel.start = ''; sel.end = ''; $('#drcInfo', m.el).textContent = fmtRange(); renderCal(cal); return; }
        // Apply
        if (e.target.closest('#drcApply')) { m.close(); onDone(sel.start, sel.end); }
      });
    }
  };

  /* ───── Main history module ───── */
  window.AFHistory = {
    mount: function (root) {
      var full = root.getAttribute('data-full') === '1';
      var f = { type: '', startDate: '', endDate: '', method: '', status: '' };
      var cursor = null, loading = false;

      function sel(name, opts) {
        return '<select name="' + name + '"><option value="">' + AF.esc(AF.t(name === 'method' ? 'method' : name === 'status' ? 'status' : 'type')) + ': ' + AF.esc(AF.t('all')) + '</option>'
          + opts.map(function (o) { return '<option value="' + o[0] + '">' + AF.esc(AF.t(o[1])) + '</option>'; }).join('') + '</select>';
      }

      function shell() {
        root.innerHTML =
          '<div class="seg-tabs" id="hTabs">'
          + '<button data-v="" class="on">' + AF.esc(AF.t('all')) + '</button>'
          + '<button data-v="deposit">' + AF.esc(AF.t('t_deposit')) + '</button>'
          + '<button data-v="withdraw">' + AF.esc(AF.t('t_withdraw')) + '</button>'
          + '</div>'
          + '<button class="link-btn" id="hFilterBtn" type="button">' + AF.icon('filter', 15) + ' ' + AF.esc(AF.t('filters')) + '</button>'
          + '<form class="filters" id="hFilters" hidden style="margin-top:10px">'
          /* custom date range trigger */
          + '<button class="btn btn-ghost btn-sm" id="hDateBtn" type="button" style="grid-column:span 2;text-align:left;gap:8px">'
          + AF.icon('calendar', 16) + ' <span id="hDateLabel">' + AF.esc(AF.lang()==='bn'?'তারিখ বাছুন':'Select dates') + '</span></button>'
          + sel('method', [['bKash', 'm_bKash'], ['Nagad', 'm_Nagad']])
          + sel('status', [['pending', 's_pending'], ['processing', 's_processing'], ['successful', 's_successful'], ['failed', 's_failed'], ['cancelled', 's_cancelled']])
          + '<button class="btn btn-primary" type="submit">' + AF.esc(AF.t('apply')) + '</button>'
          + '<button class="btn btn-ghost" type="reset">' + AF.esc(AF.t('reset')) + '</button>'
          + '</form>'
          + '<div id="hList" style="margin-top:8px"></div>'
          + '<div class="empty" id="hEmpty" hidden>' + AF.esc(AF.t('no_data')) + '</div>'
          + '<div id="hLoading" class="h-loading" hidden><span class="spin"></span></div>'
          + '<div class="muted h-count" id="hCount" hidden></div>';
      }

      function updateDateLabel() {
        var lbl = $('#hDateLabel', root); if (!lbl) return;
        if (f.startDate || f.endDate) {
          lbl.textContent = (f.startDate || '…') + ' → ' + (f.endDate || '…');
          lbl.style.color = 'var(--primary)';
        } else {
          lbl.textContent = AF.lang()==='bn'?'তারিখ বাছুন':'Select dates';
          lbl.style.color = '';
        }
      }

      function row(t) {
        var el = document.createElement('div'); el.className = 'tx'; el.setAttribute('data-id', t.id);
        var sign = t.type === 'deposit' ? '+' : '−';
        el.innerHTML =
          '<div class="tx-ic ' + t.type + '">' + AF.icon(t.type) + '</div>'
          + '<div class="tx-main"><b>' + AF.esc(AF.t('t_' + t.type)) + ' · ' + AF.esc(t.method) + '</b>'
          + '<small>' + AF.esc(AF.date(t.createdAtMs)) + ' · ' + AF.esc(AF.time(t.createdAtMs)) + '</small></div>'
          + '<div class="tx-amt">' + sign + '৳' + AF.esc(AF.money(t.amount))
          + '<br><span class="pill ' + AF.esc(t.status) + '">' + AF.esc(AF.t('s_' + t.status)) + '</span></div>';
        el.addEventListener('click', function () { detail(t); });
        return el;
      }

      var deletable = ['pending', 'cancelled', 'failed'];

      function detail(t) {
        var rows = [['tx_id', t.id, 1], ['order_id', t.orderId, 1], ['type', AF.t('t_' + t.type)], ['method', t.method], ['amount', '৳' + AF.money(t.amount)]];
        if (t.fee) { rows.push(['fee', '৳' + AF.money(t.fee)]); }
        rows.push(['status', AF.t('s_' + t.status)], ['date', AF.date(t.createdAtMs)], ['time', AF.time(t.createdAtMs)]);
        if (t.walletNo)   { rows.push(['wallet',    t.walletNo]); }
        if (t.holderName) { rows.push(['holder',    t.holderName]); }
        if (t.flowId)     { rows.push(['reference', t.flowId]); }

        var canDelete = deletable.indexOf(t.status) !== -1;
        var deleteBtn = canDelete
          ? '<button class="btn btn-danger btn-block" id="hDelBtn" type="button" style="margin-top:14px">'
            + AF.icon('trash', 16) + ' ' + AF.esc(AF.lang()==='bn'?'ইতিহাস থেকে মুছুন':'Remove from history')
            + '</button>'
          : '';

        var m = AF.modal({
          title: AF.t('tx_details'),
          html: rows.map(function (r) {
            return '<div class="kv"><span class="muted">' + AF.esc(AF.t(r[0])) + '</span>'
              + '<b style="word-break:break-all;text-align:right">' + AF.esc(r[1])
              + (r[2] ? ' <button type="button" class="link-btn" data-c="' + AF.esc(r[1]) + '" aria-label="Copy">' + AF.icon('copy', 16) + '</button>' : '')
              + '</b></div>';
          }).join('') + deleteBtn
        });

        m.body.addEventListener('click', function (e) {
          var c = e.target.closest('[data-c]'); if (c) { AF.copy(c.getAttribute('data-c')); }
          if (e.target.closest('#hDelBtn')) {
            var btn = $('#hDelBtn', m.el); AF.busy(btn, true);
            AF.api('history/delete.php', { txId: t.id }).then(function (j) {
              AF.busy(btn, false);
              if (j.success) {
                m.close();
                // Remove from DOM
                var el = root.querySelector('[data-id="' + t.id + '"]');
                if (el) {
                  el.style.transition = 'opacity .3s,transform .3s';
                  el.style.opacity = '0'; el.style.transform = 'translateX(40px)';
                  setTimeout(function () { el.remove(); var list = $('#hList', root); $('#hEmpty', root).hidden = list.children.length > 0; }, 320);
                }
                AF.toast(j.message || (AF.lang()==='bn'?'মুছে ফেলা হয়েছে':'Removed'), 'success');
              } else {
                AF.toast(j.message || 'Error', 'error');
              }
            });
          }
        });
      }

      function load(reset) {
        if (loading) { return; } loading = true;
        if (reset) { cursor = null; $('#hList', root).innerHTML = ''; }
        var ld = $('#hLoading', root); if (ld) { ld.hidden = false; }
        var body = Object.assign({}, f, { cursor: cursor, limit: full ? 15 : 8 });
        AF.api('history/get.php', body).then(function (j) {
          loading = false; if (!j.success) { if (ld) { ld.hidden = true; } return; }
          var list = $('#hList', root);
          j.data.items.forEach(function (t) { list.appendChild(row(t)); });
          cursor = j.data.next;
          $('#hEmpty', root).hidden = list.children.length > 0;
          var c = $('#hCount', root);
          if (c) {
            c.hidden = list.children.length === 0;
            c.textContent = (AF.lang() === 'bn' ? 'মোট ' : 'Total ') + AF.num(list.children.length) + (AF.lang() === 'bn' ? ' টি লেনদেন' : ' transactions');
          }
          /* No "load more" button: keep paging automatically until every transaction is shown. */
          if (cursor) { load(false); } else if (ld) { ld.hidden = true; }
        });
      }

      shell(); load(true);

      root.addEventListener('click', function (e) {
        var tab = e.target.closest('#hTabs button');
        if (tab) {
          f.type = tab.getAttribute('data-v');
          [].forEach.call(root.querySelectorAll('#hTabs button'), function (b) { b.classList.toggle('on', b === tab); });
          load(true);
        }
        if (e.target.closest('#hFilterBtn')) { var fl = $('#hFilters', root); fl.hidden = !fl.hidden; }
        if (e.target.closest('#hDateBtn')) {
          AFDateRange.open(f.startDate, f.endDate, function (s, e2) {
            f.startDate = s; f.endDate = e2; updateDateLabel();
          });
        }
      });

      $('#hFilters', root).addEventListener('submit', function (e) {
        e.preventDefault();
        var d = AF.form(e.target);
        f.method = d.method; f.status = d.status;
        load(true);
      });
      $('#hFilters', root).addEventListener('reset', function () {
        setTimeout(function () {
          f.startDate = f.endDate = f.method = f.status = '';
          updateDateLabel(); load(true);
        }, 0);
      });

      document.addEventListener('af:lang', function () { shell(); updateDateLabel(); load(true); });
    }
  };
})();
