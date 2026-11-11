/* AF MONEY SAFER - shared front-end core: icons, i18n, theme, toast, API client, modal, helpers. No secrets here. */
(function () {
  'use strict';
  var AF = window.AF = {};
  var $ = AF.$ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = AF.$$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var LS = { get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } }, set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} } };

  AF.esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };

  /* ---------- custom SVG icon system (24x24, stroke) — hand-drawn, no icon font ---------- */
  var P = {
    home: '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v9.5h13V10"/><path d="M10 19.5v-5h4v5"/>',
    user: '<circle cx="12" cy="8" r="4"/><path d="M4 20.5c1.2-4 4.2-6 8-6s6.8 2 8 6"/>',
    bell: '<path d="M6 16v-5a6 6 0 0 1 12 0v5l1.5 2h-15z"/><path d="M10 21h4"/>',
    deposit: '<path d="M12 3v12"/><path d="M6.5 10.5 12 16l5.5-5.5"/><path d="M4 20.5h16"/>',
    withdraw: '<path d="M12 16V4"/><path d="M6.5 9.5 12 4l5.5 5.5"/><path d="M4 20.5h16"/>',
    wallet: '<path d="M3.5 7.5A2.5 2.5 0 0 1 6 5h12v3"/><rect x="3.5" y="8" width="17" height="11.5" rx="2.5"/><circle cx="16.5" cy="13.8" r="1.2"/>',
    eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
    eyeoff: '<path d="M3 3l18 18"/><path d="M10.6 6.1A9.8 9.8 0 0 1 12 6c6.5 0 10 6 10 6a17 17 0 0 1-3.2 3.8M6.3 7.6A17 17 0 0 0 2 12s3.5 6 10 6c1.5 0 2.8-.3 4-.8"/>',
    mail: '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m4 7.5 8 6 8-6"/>',
    lock: '<rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/>',
    key: '<circle cx="8" cy="15" r="4"/><path d="m11 12 8.5-8.5M16 7l3 3"/>',
    shield: '<path d="M12 3 20 6v6c0 4.5-3.2 8-8 9.5C7.2 20 4 16.5 4 12V6z"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
    check: '<path d="m5 12.5 4.5 4.5L19 7.5"/>', x: '<path d="M6 6l12 12M18 6 6 18"/>',
    alert: '<path d="M12 3.5 22 20H2z"/><path d="M12 10v4.5M12 17.5v.1"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.8v.1"/>',
    moon: '<path d="M20 14.5A8.5 8.5 0 0 1 9.5 4 8.5 8.5 0 1 0 20 14.5z"/>',
    sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2.5V5M12 19v2.5M2.5 12H5M19 12h2.5M5.3 5.3l1.8 1.8M16.9 16.9l1.8 1.8M5.3 18.7l1.8-1.8M16.9 7.1l1.8-1.8"/>',
    globe: '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c2.8 2.7 2.8 15.3 0 18M12 3c-2.8 2.7-2.8 15.3 0 18"/>',
    backspace: '<path d="M9 5h11a1.5 1.5 0 0 1 1.5 1.5v11A1.5 1.5 0 0 1 20 19H9l-6-7z"/><path d="m12.5 9.5 5 5M17.5 9.5l-5 5"/>',
    camera: '<path d="M4 8h3l1.5-2.5h7L17 8h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>',
    logout: '<path d="M10 4H5.5A1.5 1.5 0 0 0 4 5.5v13A1.5 1.5 0 0 0 5.5 20H10"/><path d="m15 8 4 4-4 4M19 12H9"/>',
    chevron: '<path d="m9 6 6 6-6 6"/>', 'chevron-left': '<path d="m15 6-6 6 6 6"/>',
    copy: '<rect x="8" y="8" width="12" height="12" rx="2.5"/><path d="M16 8V6.5A2.5 2.5 0 0 0 13.5 4h-7A2.5 2.5 0 0 0 4 6.5v7A2.5 2.5 0 0 0 6.5 16H8"/>',
    trash: '<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/>',
    filter: '<line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/>',
    calendar: '<rect x="3" y="4" width="18" height="17" rx="2.5"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    /* --- added in v2 --- */
    history: '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/><path d="M12 7v5l3.5 2"/>',
    chart: '<path d="M3 3v18h18"/><path d="M7 15v-3M12 15V7M17 15v-5"/>',
    settings: '<circle cx="12" cy="12" r="3.2"/><path d="M12 2.8v2.7M12 18.5v2.7M2.8 12h2.7M18.5 12h2.7M5.2 5.2l1.9 1.9M16.9 16.9l1.9 1.9M18.8 5.2l-1.9 1.9M7.1 16.9l-1.9 1.9"/>',
    'shield-check': '<path d="M12 3 20 6v6c0 4.5-3.2 8-8 9.5C7.2 20 4 16.5 4 12V6z"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
    sparkle: '<path d="M12 3.5 13.8 9l5.5 1.8-5.5 1.8L12 18l-1.8-5.4L4.7 10.8 10.2 9z"/><path d="M18.5 15.5l.8 2.2 2.2.8-2.2.8-.8 2.2-.8-2.2-2.2-.8 2.2-.8z"/>',
    send: '<path d="M21.5 2.5 11 13"/><path d="M21.5 2.5 14.8 21.5l-3.8-8.5-8.5-3.8z"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="m20.5 20.5-4-4"/>',
    refresh: '<path d="M21 12a9 9 0 1 1-2.6-6.4"/><path d="M21 3v6h-6"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
    receipt: '<path d="M5 3h14v18l-2.3-1.6-2.4 1.6-2.3-1.6L9.7 21l-2.4-1.6L5 21z"/><path d="M9 8h6M9 12h6"/>',
    grid: '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
    phone: '<path d="M5 3.5h3.5l1.8 4.5-2.3 1.6a12.5 12.5 0 0 0 6.4 6.4l1.6-2.3 4.5 1.8V19a2 2 0 0 1-2.2 2A16.5 16.5 0 0 1 3 5.7a2 2 0 0 1 2-2.2z"/>',
    star: '<path d="m12 3 2.7 5.6 6.1.8-4.5 4.2 1.1 6-5.4-3-5.4 3 1.1-6L3.2 9.4l6.1-.8z"/>',
    'arrow-right': '<path d="M4 12h15.5"/><path d="m14 6 6 6-6 6"/>',
    card: '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19"/><path d="M6 15h4"/>',
    download: '<path d="M12 3.5V15"/><path d="M6.8 10.5 12 15.7l5.2-5.2"/><path d="M4 19.5h16"/>',
    zap: '<path d="M13 2.5 4.5 14H11l-1 7.5L18.5 10H12z"/>'
  };
  AF.icon = function (n, s) { s = s || 20; return '<svg class="ic" width="' + s + '" height="' + s + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (P[n] || '') + '</svg>'; };
  AF.logo = function (s) { return '<img src="/assets/img/logo.svg" width="' + s + '" height="' + s + '" alt="AF MONEY SAFER">'; };

  var COLORS = ['#12b886', '#f5c451', '#4c8bf5', '#e2136e', '#f6921e', '#8b5cf6', '#14b8a6', '#ef4444'];
  AF.avatar = function (a, size) {
    size = size || 40;
    if (a && a.type === 'upload') { return '<img alt="" src="/api/profile/avatar.php?v=' + encodeURIComponent(a.key) + '">'; }
    var i = a && /^p[1-8]$/.test(a.key || '') ? parseInt(a.key.slice(1), 10) - 1 : -1;
    if (i < 0) { return AF.icon('user', Math.round(size * 0.55)); }
    var c = COLORS[i];
    return '<svg viewBox="0 0 64 64" width="100%" height="100%" aria-hidden="true"><rect width="64" height="64" fill="' + c + '"/><circle cx="32" cy="25" r="11" fill="#fff" opacity=".92"/><path d="M10 64c2-15 12-22 22-22s20 7 22 22z" fill="#fff" opacity=".92"/>' + (i % 2 ? '<circle cx="32" cy="25" r="11" fill="none" stroke="' + c + '" stroke-width="3" opacity=".35"/>' : '') + '</svg>';
  };

  /* ---------- language ---------- */
  var lang = LS.get('af_lang') === 'en' ? 'en' : 'bn';
  AF.lang = function () { return lang; };
  AF.t = function (k, p) {
    var s = (window.AF_I18N[lang] || {})[k]; if (s == null) { s = window.AF_I18N.en[k]; } if (s == null) { return k; }
    if (p) { Object.keys(p).forEach(function (x) { s = s.split('{' + x + '}').join(p[x]); }); }
    return s;
  };
  AF.apply = function (root) {
    root = root || document;
    $$('[data-i18n]', root).forEach(function (e) { e.textContent = AF.t(e.getAttribute('data-i18n')); });
    $$('[data-i18n-ph]', root).forEach(function (e) { e.setAttribute('placeholder', AF.t(e.getAttribute('data-i18n-ph'))); });
    $$('[data-icon]', root).forEach(function (e) { e.innerHTML = AF.icon(e.getAttribute('data-icon'), parseInt(e.getAttribute('data-size') || '20', 10)); });
    $$('[data-logo]', root).forEach(function (e) { e.innerHTML = AF.logo(parseInt(e.getAttribute('data-logo'), 10)); });
    $$('.lang-tag', root).forEach(function (e) { e.textContent = lang === 'bn' ? 'বাং' : 'EN'; });
  };
  AF.setLang = function (l, silent) {
    if (l !== 'bn' && l !== 'en') { return; }
    lang = l; LS.set('af_lang', l);
    document.documentElement.setAttribute('lang', l);
    document.cookie = 'lang=' + l + ';path=/;max-age=31536000;SameSite=Lax' + (location.protocol === 'https:' ? ';Secure' : '');
    AF.apply(); AF.syncThemeIcon();
    document.dispatchEvent(new CustomEvent('af:lang'));
    if (!silent && $('meta[name=csrf]')) { AF.api('profile/preferences.php', { lang: l, theme: AF.theme() }, { quiet: true }); }
  };

  /* ---------- theme ---------- */
  AF.theme = function () { return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light'; };
  AF.syncThemeIcon = function () { $$('[data-action=theme] [data-icon]').forEach(function (e) { e.setAttribute('data-icon', AF.theme() === 'dark' ? 'sun' : 'moon'); e.innerHTML = AF.icon(AF.theme() === 'dark' ? 'sun' : 'moon'); }); };
  AF.setTheme = function (t, silent) {
    if (t !== 'dark' && t !== 'light') { return; }
    document.documentElement.setAttribute('data-theme', t); LS.set('af_theme', t); AF.syncThemeIcon();
    document.dispatchEvent(new CustomEvent('af:theme'));
    if (!silent && $('meta[name=csrf]')) { AF.api('profile/preferences.php', { lang: lang, theme: t }, { quiet: true }); }
  };

  /* ---------- toast ---------- */
  AF.toast = function (msg, type, ms) {
    var box = $('#toasts'); if (!box || !msg) { return; }
    var el = document.createElement('div'); el.className = 'toast ' + (type || 'info'); el.setAttribute('role', 'status');
    var ic = type === 'success' ? 'check' : type === 'error' ? 'alert' : 'info';
    el.innerHTML = AF.icon(ic, 20); var sp = document.createElement('span'); sp.textContent = msg; el.appendChild(sp);
    box.appendChild(el);
    var close = function () { el.classList.add('out'); setTimeout(function () { el.remove(); }, 260); };
    el.addEventListener('click', close); setTimeout(close, ms || 4500);
    while (box.children.length > 3) { box.firstChild.remove(); }
  };

  /* ---------- API client ---------- */
  var csrf = (($('meta[name=csrf]') || {}).content) || '', boot = null;
  /* boot is cached for the page lifetime — pass force=true after a login/logout,
     or the guard below keeps seeing the pre-login "not authenticated" snapshot. */
  AF.boot = function (force) {
    if (force) { boot = null; }
    if (boot) { return boot; }
    boot = fetch('/api/auth/csrf.php?lang=' + lang, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
      if (j.success) { csrf = j.data.csrfToken; return j.data; } throw new Error('boot');
    }).catch(function () { boot = null; return { turnstileSiteKey: '', authenticated: false, otp: { length: 6, resendIn: 60 } }; });
    return boot;
  };
  function request(path, body, method, retry) {
    var p = csrf ? Promise.resolve() : AF.boot();
    return p.then(function () {
      var opt = { method: method, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf } };
      var url = '/api/' + path;
      if (method === 'GET') { url += (path.indexOf('?') > -1 ? '&' : '?') + 'lang=' + lang; }
      else if (body instanceof FormData) { opt.body = body; }
      else { opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(Object.assign({ lang: lang }, body || {})); }
      return fetch(url, opt);
    }).then(function (r) { return r.json(); }).then(function (j) {
      if (j && j.code === 'CSRF_INVALID' && !retry) { csrf = ''; boot = null; return request(path, body, method, true); }
      return j;
    });
  }
  /** Always resolves {success, code, message, data}. Errors are toasted unless opts.quiet. */
  AF.api = function (path, body, opts) {
    opts = opts || {}; var method = opts.method || (body === undefined ? 'GET' : 'POST');
    return request(path, body, method, false).catch(function () { return { success: false, code: 'NETWORK', message: AF.t('network_error'), data: {} }; })
      .then(function (j) {
        if (!j.success) {
          if (j.code === 'UNAUTHENTICATED' && !/^auth\//.test(path)) { location.replace('/login.php'); }
          if (!opts.quiet) { AF.toast(j.message, 'error'); }
        } else if (opts.ok) { AF.toast(j.message, 'success'); }
        j.data = j.data || {}; return j;
      });
  };
  AF.busy = function (btn, on) {
    if (!btn) { return; }
    if (on) { btn._h = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<span class="spin"></span>'; }
    else { btn.disabled = false; if (btn._h != null) { btn.innerHTML = btn._h; } }
  };
  AF.fieldErrors = function (form, fields) {
    $$('[data-err]', form).forEach(function (e) { e.textContent = (fields && fields[e.getAttribute('data-err')]) || ''; });
  };
  AF.form = function (form) { var o = {}; $$('input[name],select[name]', form).forEach(function (i) { o[i.name] = i.value; }); return o; };

  /* ---------- Turnstile ---------- */
  var tsLoaded = null;
  AF.turnstile = function (el) {
    return AF.boot().then(function (b) {
      var none = { token: function () { return ''; }, reset: function () {} };
      if (!b.turnstileSiteKey) { el.textContent = 'CAPTCHA is not configured'; return none; }
      tsLoaded = tsLoaded || new Promise(function (res, rej) {
        var s = document.createElement('script'); s.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit'; s.async = true; s.onload = res; s.onerror = rej; document.head.appendChild(s);
      });
      return tsLoaded.then(function () {
        var token = '', id = window.turnstile.render(el, { sitekey: b.turnstileSiteKey, theme: AF.theme(), callback: function (t) { token = t; }, 'expired-callback': function () { token = ''; }, 'error-callback': function () { token = ''; } });
        return { token: function () { return token; }, reset: function () { token = ''; try { window.turnstile.reset(id); } catch (e) {} } };
      }).catch(function () { el.textContent = AF.t('network_error'); return none; });
    });
  };

  /* ---------- modal ---------- */
  AF.modal = function (o) {
    var back = document.createElement('div'); back.className = 'modal-back';
    back.innerHTML = '<div class="modal" role="dialog" aria-modal="true"><div class="modal-head"><h3></h3><button class="icon-btn" type="button" aria-label="Close" data-x>' + AF.icon('x') + '</button></div><div class="modal-body"></div></div>';
    $('h3', back).textContent = o.title || '';
    var body = $('.modal-body', back); if (o.html != null) { body.innerHTML = o.html; } if (o.node) { body.appendChild(o.node); }
    var api = { el: back, body: body, close: function () { back.remove(); document.removeEventListener('keydown', esc); if (o.onClose) { o.onClose(); } } };
    function esc(e) { if (e.key === 'Escape') { api.close(); } }
    document.addEventListener('keydown', esc);
    back.addEventListener('click', function (e) { if (e.target === back || e.target.closest('[data-x]')) { api.close(); } });
    document.body.appendChild(back); AF.apply(back);
    // Scroll modal to top and prevent body scroll while open
    var modal = back.querySelector('.modal');
    if (modal) { modal.scrollTop = 0; }
    var prevOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    var origClose = api.close;
    api.close = function () { document.body.style.overflow = prevOverflow; origClose(); };
    back.addEventListener('click', function (e) { if (e.target === back) { document.body.style.overflow = prevOverflow; } });
    return api;
  };

  /* ---------- formatting ---------- */
  var loc = function () { return lang === 'bn' ? 'bn-BD' : 'en-US'; };
  AF.money = function (p) { return new Intl.NumberFormat(loc(), { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format((Number(p) || 0) / 100); };
  AF.num = function (n) { return new Intl.NumberFormat(loc()).format(n); };
  AF.date = function (ms) { return ms ? new Intl.DateTimeFormat(loc(), { year: 'numeric', month: 'short', day: 'numeric' }).format(new Date(ms)) : '—'; };
  AF.time = function (ms) { return ms ? new Intl.DateTimeFormat(loc(), { hour: '2-digit', minute: '2-digit' }).format(new Date(ms)) : '—'; };
  AF.go = function (u) { document.body.classList.add('leaving'); setTimeout(function () { location.href = u; }, 160); };
  AF.copy = function (text) { if (navigator.clipboard) { navigator.clipboard.writeText(text).then(function () { AF.toast(AF.t('copied'), 'success', 1500); }); } };
  AF.digits = function (input, max) { input.addEventListener('input', function () { input.value = input.value.replace(/\D/g, '').slice(0, max || 9); }); };
  AF.ready = function (fn) { if (window.AF_READY) { fn(); } else { document.addEventListener('af:ready', fn, { once: true }); } };

  /* ---------- motion helpers (v2) ---------- */
  var reducedMotion = function () { try { return window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) { return false; } };
  /** Animate a money element to a paisa value, rendering "৳" + AF.money(). Instant when reduced motion is on. */
  AF.countUp = function (el, paisa, ms) {
    if (!el) { return; }
    var target = Number(paisa) || 0;
    var render = function (v) { el.textContent = '৳' + AF.money(Math.round(v)); };
    if (reducedMotion()) { render(target); return; }
    var from = 0;
    var cur = (el.textContent || '').replace(/[^\d.,]/g, '').replace(/,/g, '');
    if (cur) { var parsed = parseFloat(cur); if (!isNaN(parsed)) { from = parsed * 100; } }
    if (from === target) { render(target); return; }
    var dur = ms || 650, start = null;
    var step = function (t) {
      if (start == null) { start = t; }
      var k = Math.min(1, (t - start) / dur), e = 1 - Math.pow(1 - k, 3);
      render(from + (target - from) * e);
      if (k < 1) { requestAnimationFrame(step); }
    };
    requestAnimationFrame(step);
  };
  /** Scroll-reveal: observes .reveal elements and adds .in when visible. Safe to call repeatedly. */
  AF.reveal = function (root) {
    var els = $$('.reveal:not(.in)', root || document);
    if (!els.length) { return; }
    if (reducedMotion() || !('IntersectionObserver' in window)) { els.forEach(function (e) { e.classList.add('in'); }); return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { threshold: 0.12, rootMargin: '0px 0px -8px 0px' });
    els.forEach(function (e) { io.observe(e); });
  };
  /** Button ripple: appends a span.ripple at the click point, removed after the animation. */
  function ripple(e) {
    var t = e.target.closest && e.target.closest('.btn,.icon-btn,.chip,.method,.action-card,.menu-item,.seg-tabs button,.tabs button,.numpad button');
    if (!t) { return; }
    var r = t.getBoundingClientRect();
    var d = Math.max(r.width, r.height) * 1.1;
    var s = document.createElement('span'); s.className = 'ripple';
    s.style.width = s.style.height = d + 'px';
    s.style.left = (e.clientX - r.left - d / 2) + 'px';
    s.style.top = (e.clientY - r.top - d / 2) + 'px';
    t.appendChild(s);
    setTimeout(function () { s.remove(); }, 650);
  }

  /* ---------- logged-in snapshot (balance, limits, avatar, unread) ---------- */
  var mePromise = null;
  AF.me = function (force) {
    if (!mePromise || force) { mePromise = AF.api('dashboard/get.php', undefined, { quiet: true }).then(function (j) { return j.success ? j.data : null; }); }
    return mePromise;
  };
  AF.top = function (me) {
    if (!me) { return; }
    var a = $('#topAvatar'); if (a) { a.removeAttribute('data-icon'); a.innerHTML = AF.avatar(me.user.avatar, 40); }
    var b = $('#nbadge'); if (b) { b.hidden = !me.unread; b.textContent = me.unread > 9 ? '9+' : me.unread; }
  };

  /* ---------- init ---------- */
  function init() {
    AF.apply(); AF.syncThemeIcon(); AF.reveal();
    document.addEventListener('click', function (e) {
      ripple(e);
      var a = e.target.closest('[data-action]');
      if (a) { var k = a.getAttribute('data-action'); if (k === 'lang') { AF.setLang(lang === 'bn' ? 'en' : 'bn'); } if (k === 'theme') { AF.setTheme(AF.theme() === 'dark' ? 'light' : 'dark'); } return; }
      var eye = e.target.closest('[data-eye]');
      if (eye) { var i = $('input', eye.parentNode); var show = i.type === 'password'; i.type = show ? 'text' : 'password'; eye.innerHTML = AF.icon(show ? 'eyeoff' : 'eye'); return; }
      var l = e.target.closest('a[href^="/"]');
      if (l && !l.target && !e.metaKey && !e.ctrlKey && !e.shiftKey && e.button === 0) { e.preventDefault(); AF.go(l.getAttribute('href')); }
    });
    window.addEventListener('pageshow', function (e) { if (e.persisted) { document.body.classList.remove('leaving'); } });
    window.AF_READY = true; document.dispatchEvent(new CustomEvent('af:ready'));
    if ($('meta[name=csrf]') && !$('body.page-dashboard')) { AF.me().then(AF.top); }
  }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
