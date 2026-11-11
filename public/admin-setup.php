<?php
declare(strict_types=1);

/**
 * One-time admin setup page: /admin-setup.php
 * Creates the FIRST admin account (email + password). No Shell needed.
 * After an admin exists this page answers 404 ("this page does not exist").
 */
require __DIR__ . '/../src/bootstrap.php';

use App\Services\AdminSetup;

if (AdminSetup::alreadySetup()) {
    http_response_code(404);
    ?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>404 — Page not found</title></head>
<body style="margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0B3D2E;color:#fff;font:16px/1.5 system-ui,sans-serif">
<div style="text-align:center"><h1 style="font-size:2.4rem;margin:0">404</h1><p>This page does not exist.</p></div></body></html><?php
    exit;
}

$root = __DIR__;
$v = static fn(string $f): string => (string)@filemtime($root . $f);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Admin Setup | AF MONEY SAFER</title>
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
      <p class="brand-trust"><span data-icon="shield"></span>Admin setup — one time only</p>
    </div>
  </aside>
  <section class="auth-card">
    <div class="auth-head"><div data-logo="64"></div><h1>Admin Setup</h1>
      <p class="muted">Create the first admin account. This page works only once — after setup it disappears.</p></div>
    <form id="f" novalidate autocomplete="off">
      <label class="field"><span>Admin Gmail</span>
        <div class="input"><span data-icon="mail"></span><input type="email" name="email" autocomplete="email" inputmode="email" placeholder="admin@gmail.com" required></div></label>
      <label class="field"><span>Password</span>
        <div class="input"><span data-icon="lock"></span><input type="password" name="password" autocomplete="new-password" required>
          <button type="button" class="eye" data-eye aria-label="Show/Hide"><span data-icon="eye"></span></button></div>
        <small class="muted">8+ characters, with a lowercase letter, an uppercase letter and a number.</small></label>
      <label class="field"><span>Confirm password</span>
        <div class="input"><span data-icon="lock"></span><input type="password" name="confirmPassword" autocomplete="new-password" required>
          <button type="button" class="eye" data-eye aria-label="Show/Hide"><span data-icon="eye"></span></button></div></label>
      <div id="captcha" class="captcha"></div>
      <button class="btn btn-primary btn-block" id="submit" type="submit">Register admin</button>
    </form>
    <p class="auth-foot"><span class="muted">Use a strong password you will remember — this is the only way to create an admin.</span></p>
  </section>
</div>
<script src="/assets/js/i18n.js?v=<?= $v('/assets/js/i18n.js') ?>" defer></script>
<script src="/assets/js/core.js?v=<?= $v('/assets/js/core.js') ?>" defer></script>
<script src="/assets/js/pages/admin-setup.js?v=<?= $v('/assets/js/pages/admin-setup.js') ?>" defer></script>
</body>
</html>
