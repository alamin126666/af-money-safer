<?php
/** @var string $name @var string $title @var array $o @var ?string $csrf @var callable $v */
$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$navHome = in_array($name, ['dashboard', 'deposit', 'withdraw', 'history', 'notifications'], true);
?>
<!DOCTYPE html>
<html lang="bn" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>AF MONEY SAFER | <?= $h($title) ?></title>
<meta name="color-scheme" content="light dark">
<meta name="robots" content="noindex, nofollow">
<?php if ($csrf !== null): ?><meta name="csrf" content="<?= $h($csrf) ?>"><?php endif; ?>
<link rel="icon" href="/assets/img/logo.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/css/app.css?v=<?= $v('/assets/css/app.css') ?>">
<script src="/assets/js/theme-init.js"></script>
</head>
<body class="page-<?= $h($name) ?><?= $o['app'] ? ' has-nav' : '' ?>">
<div id="toasts" aria-live="polite"></div>
<?php if ($o['app']): ?>
<header class="topbar">
  <a class="brand" href="/dashboard.php"><span data-logo="30"></span><span class="brand-name">AF MONEY SAFER</span></a>
  <nav class="top-actions">
    <button class="icon-btn" data-action="lang" aria-label="Language"><span data-icon="globe"></span><b class="lang-tag"></b></button>
    <button class="icon-btn" data-action="theme" aria-label="Theme"><span data-icon="moon"></span></button>
    <a class="icon-btn" href="/notifications.php" aria-label="Notifications"><span data-icon="bell"></span><i class="badge" id="nbadge" hidden>0</i></a>
    <a class="icon-btn avatar-btn" href="/profile.php" aria-label="Profile"><span id="topAvatar" data-icon="user"></span></a>
  </nav>
</header>
<?php else: ?>
<div class="float-tools">
  <button class="icon-btn" data-action="lang" aria-label="Language"><span data-icon="globe"></span><b class="lang-tag"></b></button>
  <button class="icon-btn" data-action="theme" aria-label="Theme"><span data-icon="moon"></span></button>
</div>
<?php endif; ?>
<main class="wrap<?= $o['app'] ? ' wrap-app' : '' ?>" id="main">
<?php require $root . '/templates/pages/' . $name . '.php'; ?>
</main>
<?php if ($o['app']): ?>
<nav class="bottom-nav" aria-label="Main">
  <a href="/dashboard.php" class="<?= $navHome ? 'active' : '' ?>"><span data-icon="home" data-size="24"></span><span data-i18n="nav_home"></span></a>
  <a href="/profile.php" class="<?= $name === 'profile' ? 'active' : '' ?>"><span data-icon="user" data-size="24"></span><span data-i18n="nav_profile"></span></a>
</nav>
<?php endif; ?>
<script src="/assets/js/i18n.js?v=<?= $v('/assets/js/i18n.js') ?>" defer></script>
<script src="/assets/js/core.js?v=<?= $v('/assets/js/core.js') ?>" defer></script>
<?php foreach ($o['js'] as $js): ?><script src="/assets/js/<?= $h($js) ?>.js?v=<?= $v('/assets/js/' . $js . '.js') ?>" defer></script>
<?php endforeach; ?>
<script src="/assets/js/pages/<?= $h($name) ?>.js?v=<?= $v('/assets/js/pages/' . $name . '.js') ?>" defer></script>
</body>
</html>
