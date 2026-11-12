/* Custom number pad + 6 code boxes (no native keyboard needed). Supports physical keyboard and paste. */
(function () {
  'use strict';
  AF.otpPad = function (o) {
    var len = o.length || 6, v = '', boxes = o.boxes, pad = o.pad;
    boxes.innerHTML = ''; pad.innerHTML = '';
    for (var i = 0; i < len; i++) { boxes.appendChild(document.createElement('span')); }
    var keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'C', '0', 'B'];
    keys.forEach(function (k) {
      var b = document.createElement('button'); b.type = 'button'; b.setAttribute('data-k', k);
      if (k === 'B') { b.innerHTML = AF.lotIcon('backspace', 24); b.setAttribute('aria-label', 'Backspace'); }
      else if (k === 'C') { b.className = 'fn'; b.textContent = 'Clear'; b.setAttribute('aria-label', 'Clear'); }
      else { b.textContent = AF.lang() === 'bn' ? new Intl.NumberFormat('bn-BD', { useGrouping: false }).format(+k) : k; b.setAttribute('aria-label', k); }
      pad.appendChild(b);
    });
    AF.apply(pad);
    function render() {
      var s = boxes.children;
      for (var i = 0; i < len; i++) {
        s[i].textContent = v[i] ? (AF.lang() === 'bn' ? new Intl.NumberFormat('bn-BD', { useGrouping: false }).format(+v[i]) : v[i]) : '';
        s[i].className = i === v.length || (v.length === len && i === len - 1) ? 'on' : '';
      }
    }
    function set(n) { v = n.replace(/\D/g, '').slice(0, len); render(); if (o.onChange) { o.onChange(v); } if (v.length === len && o.onComplete) { o.onComplete(v); } }
    pad.addEventListener('click', function (e) {
      var b = e.target.closest('button'); if (!b) { return; }
      var k = b.getAttribute('data-k'); set(k === 'C' ? '' : k === 'B' ? v.slice(0, -1) : v + k);
    });
    function key(e) {
      if (/^(INPUT|TEXTAREA|SELECT)$/.test((e.target.tagName || ''))) { return; }
      if (/^\d$/.test(e.key)) { set(v + e.key); } else if (e.key === 'Backspace') { set(v.slice(0, -1)); } else if (e.key === 'Delete' || e.key === 'Escape') { set(''); }
    }
    function paste(e) {
      if (/^(INPUT|TEXTAREA)$/.test((e.target.tagName || ''))) { return; }
      var t = (e.clipboardData || window.clipboardData).getData('text'); if (t) { e.preventDefault(); set(t); }
    }
    document.addEventListener('keydown', key); document.addEventListener('paste', paste); render();
    document.addEventListener('af:lang', render);
    return {
      get: function () { return v; }, clear: function () { set(''); },
      shake: function () { boxes.classList.remove('shake'); void boxes.offsetWidth; boxes.classList.add('shake'); },
      destroy: function () { document.removeEventListener('keydown', key); document.removeEventListener('paste', paste); document.removeEventListener('af:lang', render); }
    };
  };

  /** Resend countdown helper. */
  AF.countdown = function (btn, until, onTick) {
    var timer;
    function tick() {
      var s = Math.max(0, Math.ceil((until() - Date.now()) / 1000));
      if (s > 0) { btn.disabled = true; btn.textContent = AF.t('resend_in', { s: AF.num(s) }); } else { btn.disabled = false; btn.textContent = AF.t('resend_code'); clearInterval(timer); }
    }
    tick(); timer = setInterval(tick, 500); document.addEventListener('af:lang', tick);
    return { restart: function () { clearInterval(timer); tick(); timer = setInterval(tick, 500); } };
  };
})();
