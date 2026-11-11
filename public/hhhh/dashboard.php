<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\Core\Session;
use App\Core\Csrf;
use App\Services\AdminGuard;
use App\Core\Firebase;

Session::start();

// Must be logged in and admin-verified
if (empty($_SESSION['uid']) || empty($_SESSION['adminVerified'])) {
    header('Location: /hhhh/login.php'); exit;
}

// Verify admin status still active
$uid = $_SESSION['uid'];
$adminDoc = AdminGuard::adminDoc($uid);
if (!$adminDoc) {
    session_destroy();
    header('Location: /hhhh/login.php'); exit;
}

$role = $_SESSION['adminRole'] ?? $adminDoc['role'] ?? 'viewer';
$csrf = Csrf::token();

// Get user info
$snap = Firebase::db()->collection('users')->document($uid)->snapshot();
$adminName = $snap->exists() ? ($snap->data()['fullName'] ?? 'Admin') : 'Admin';
?>
<!DOCTYPE html>
<html lang="bn" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Admin Dashboard — AF MONEY SAFER</title>
<meta name="robots" content="noindex,nofollow">
<meta name="csrf" content="<?= htmlspecialchars($csrf) ?>">
<link rel="stylesheet" href="/assets/css/app.css?v=<?= filemtime(dirname(__DIR__) . '/assets/css/app.css') ?>">
<style>
.admin-topbar{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:var(--card);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:40}
.admin-brand{display:flex;align-items:center;gap:8px;font-weight:700;font-size:1rem;color:var(--primary)}
.admin-brand-mark{background:var(--primary);color:#fff;border-radius:10px;width:34px;height:34px;display:flex;align-items:center;justify-content:center}
.admin-actions{display:flex;align-items:center;gap:8px}
.role-badge{background:color-mix(in srgb,var(--primary) 12%,transparent);color:var(--primary);border-radius:20px;padding:3px 10px;font-size:.78rem;font-weight:700}
.admin-wrap{max-width:900px;margin:0 auto;padding:16px}
.tabs{display:flex;gap:6px;overflow-x:auto;margin-bottom:16px;padding-bottom:4px;scrollbar-width:none}
.tabs::-webkit-scrollbar{display:none}
.tabs button{flex-shrink:0;padding:9px 16px;border-radius:12px;border:1.5px solid var(--border);background:var(--card);cursor:pointer;font-weight:600;color:var(--muted);font-size:.88rem;transition:all .15s}
.tabs button.on{background:var(--primary);border-color:var(--primary);color:#fff}
#pane .card{margin-bottom:12px}
.stat{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)}
.stat:last-child{border-bottom:none}
.filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
.filters .txt{flex:1;min-width:140px;padding:9px 12px;border:1.5px solid var(--border);border-radius:12px;background:var(--input);color:var(--text);font-size:.88rem}
.filters select{padding:9px 12px;border:1.5px solid var(--border);border-radius:12px;background:var(--input);color:var(--text);font-size:.88rem}
</style>
</head>
<body>
<div class="admin-topbar">
  <div class="admin-brand">
    <div class="admin-brand-mark">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    </div>
    <span>Admin Panel</span>
  </div>
  <div class="admin-actions">
    <span class="role-badge"><?= htmlspecialchars(ucfirst($role)) ?></span>
    <span style="color:var(--muted);font-size:.82rem"><?= htmlspecialchars(mb_substr($adminName,0,16)) ?></span>
    <button class="icon-btn" data-action="theme"><span data-icon="moon"></span></button>
    <button class="btn btn-sm" id="logoutBtn" style="padding:7px 14px;font-size:.82rem">লগআউট</button>
  </div>
</div>
<div class="admin-wrap">
  <div class="tabs" id="tabs">
    <button data-t="overview" class="on">Overview</button>
    <button data-t="users">Users</button>
    <button data-t="tx">Transactions</button>
    <button data-t="settings">Settings</button>
    <button data-t="audit">Audit Log</button>
  </div>
  <section id="pane"></section>
</div>
<script src="/assets/js/theme-init.js"></script>
<script src="/assets/js/i18n.js?v=<?= filemtime(dirname(__DIR__) . '/assets/js/i18n.js') ?>" defer></script>
<script src="/assets/js/core.js?v=<?= filemtime(dirname(__DIR__) . '/assets/js/core.js') ?>" defer></script>
<script src="/assets/js/pages/admin.js?v=<?= filemtime(dirname(__DIR__) . '/assets/js/pages/admin.js') ?>" defer></script>
<script>
AF.ready(function(){
  document.getElementById('logoutBtn').addEventListener('click',function(){
    AF.api('auth/logout.php',{}).then(function(){
      try{sessionStorage.clear();}catch(e){}
      location.href='/hhhh/login.php';
    });
  });
  // Apply icons
  if(AF.apply){AF.apply(document.querySelector('.admin-topbar'));}
});
</script>
</body>
</html>
