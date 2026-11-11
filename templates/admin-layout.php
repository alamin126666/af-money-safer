<?php
/** Admin shell layout. @var string $name @var string $title @var array $o @var ?string $csrf @var callable $v @var ?array $me @var ?string $adminRole */
$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$nav = [
    ['admin/dashboard', 'Dashboard', 'home'],
    ['admin/users', 'Users', 'user'],
    ['admin/transactions', 'Transactions', 'history'],
    ['admin/settings', 'Settings', 'settings'],
    ['admin/audit', 'Audit log', 'clock'],
];
$who = ($me['fullName'] ?? 'Admin') . ' · ' . ($adminRole ?? 'admin');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Admin · <?= $h($title) ?> | AF MONEY SAFER</title>
<meta name="color-scheme" content="light dark">
<meta name="robots" content="noindex, nofollow">
<?php if ($csrf !== null): ?><meta name="csrf" content="<?= $h($csrf) ?>"><?php endif; ?>
<link rel="icon" href="/assets/img/logo.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/css/app.css?v=<?= $v('/assets/css/app.css') ?>">
<script src="/assets/js/theme-init.js"></script>
</head>
<body class="admin-page">
<a class="skip-link" href="#main">Skip to content</a>
<div id="toasts" aria-live="polite"></div>
<div class="admin-shell">
  <aside class="admin-side">
    <a class="admin-brand" href="/admin/dashboard.php"><span data-logo="30"></span><span>AF MONEY SAFER <em>Admin</em></span></a>
    <nav class="admin-nav" aria-label="Admin">
      <?php foreach ($nav as [$p, $label, $ic]): ?>
        <a href="/#/admin/<?= substr($p, 6) ?>" class="<?= $name === $p ? 'on' : '' ?>"><span data-icon="<?= $ic ?>"></span><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="admin-side-foot">
      <a href="/dashboard.php"><span data-icon="eye"></span>View site</a>
      <a href="/admin/logout.php"><span data-icon="logout"></span>Logout</a>
    </div>
  </aside>
  <div class="admin-main">
    <header class="admin-top">
      <h1><?= $h($title) ?></h1>
      <div class="admin-who"><span class="admin-ava"><?= $h(mb_substr($me['fullName'] ?? 'A', 0, 1)) ?></span><span><?= $h($who) ?></span>
        <a class="icon-btn" href="/admin/logout.php" aria-label="Logout"><span data-icon="logout"></span></a>
      </div>
    </header>
    <main class="admin-content" id="main">
      <?php require $root . '/templates/pages/' . $name . '.php'; ?>
    </main>
  </div>
</div>
<script src="/assets/js/i18n.js?v=<?= $v('/assets/js/i18n.js') ?>" defer></script>
<script src="/assets/js/core.js?v=<?= $v('/assets/js/core.js') ?>" defer></script>
<?php foreach ($o['js'] as $js): ?><script src="/assets/js/<?= $h($js) ?>.js?v=<?= $v('/assets/js/' . $js . '.js') ?>" defer></script>
<?php endforeach; ?>
<script src="/assets/js/pages/<?= $h($name) ?>.js?v=<?= $v('/assets/js/pages/' . $name . '.js') ?>" defer></script>
</body>
</html>
