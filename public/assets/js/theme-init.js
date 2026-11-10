(function () {
  try {
    var t = localStorage.getItem('af_theme');
    if (t !== 'dark' && t !== 'light') { t = window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
    document.documentElement.setAttribute('data-theme', t);
    var l = localStorage.getItem('af_lang');
    document.documentElement.setAttribute('lang', l === 'en' ? 'en' : 'bn');
  } catch (e) {}
})();
