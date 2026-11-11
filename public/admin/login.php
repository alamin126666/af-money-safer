<?php
declare(strict_types=1);

/** Admin login page: /admin/login.php (standalone — no session needed to render). */
$root = __DIR__ . '/..';
$v = static fn(string $f): string => (string)@filemtime(dirname(__DIR__) . $f);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Admin Login | AF MONEY SAFER</title>
<meta name="color-scheme" content="light dark">
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="/assets/img/logo.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/css/app.css?v=<?= $v('/assets/css/app.css') ?>">
<script src="/assets/js/theme-init.js"></script>
</head>
<body>
<div id="toasts" aria-live="polite"></div>
<div class="auth-layout">
  <aside class="brand-panel">
    <div class="brand-panel-inner">
      <div data-logo="64"></div>
      <h2>AF MONEY SAFER</h2>
      <p class="muted">Safe deposits &amp; withdrawals</p>
      <div class="brand-stats">
        <div class="brand-stat"><b>100%</b><span>Secure</span></div>
        <div class="brand-stat"><b>24/7</b><span>Available</span></div>
        <div class="brand-stat"><b>2</b><span>Methods</span></div>
      </div>
      <p class="brand-trust"><span data-icon="shield"></span>Admin panel — staff only</p>
    </div>
  </aside>
  <section class="auth-card">
    <div class="auth-head"><div data-logo="64"></div><h1>Admin Login</h1>
      <p class="muted">Sign in with your admin account to open the admin panel.</p></div>
    <form id="f" novalidate autocomplete="on">
      <label class="field"><span>Admin email</span>
        <div class="input"><span data-icon="mail"></span><input type="email" name="email" autocomplete="email" inputmode="email" required></div></label>
      <label class="field"><span>Password</span>
        <div class="input"><span data-icon="lock"></span><input type="password" name="password" autocomplete="current-password" required>
          <button type="button" class="eye" data-eye aria-label="Show/Hide"><span data-icon="eye"></span></button></div></label>
      <div id="captcha" class="captcha"></div>
      <button class="btn btn-primary btn-block" id="submit" type="submit">Login to admin panel</button>
    </form>
    <div class="auth-links"><a href="/dashboard.php">← Back to site</a></div>
    <p class="auth-foot"><span class="muted">First time here?</span> <a href="/#/admin-setup">Create the admin account</a></p>
  </section>
</div>
<script src="/assets/js/i18n.js?v=<?= $v('/assets/js/i18n.js') ?>" defer></script>
<script src="/assets/js/core.js?v=<?= $v('/assets/js/core.js') ?>" defer></script>
<script src="/assets/js/pages/admin-login.js?v=<?= $v('/assets/js/pages/admin-login.js') ?>" defer></script>
</body>
</html>
