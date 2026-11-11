<?php
declare(strict_types=1);

/**
 * One-time web-based admin setup endpoint.
 * No Shell access required. Works on Render free plan.
 *
 * Usage:
 *   POST /api/admin/setup.php
 *   Body: { "token": "<ADMIN_SETUP_TOKEN env var>", "uid": "<firebase uid>", "role": "super" }
 *
 * Set the ADMIN_SETUP_TOKEN env var in Render dashboard (a long random string).
 * After first admin is created, the token is still required for all future calls
 * (this is NOT a one-time-only endpoint — that's fine since the token stays secret).
 *
 * GET /api/admin/setup.php  → shows an HTML form (convenient for browser use)
 */

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Config;
use App\Core\Firebase;

$setupToken = (string)Config::get('admin.setup_token', '');

// Render as HTML form for GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $appName = htmlspecialchars((string)Config::get('app.name', 'AF MONEY SAFER'));
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Setup — <?= $appName ?></title>
<style>
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
  background:#0a110e;color:#e8f1ed;font:16px/1.5 system-ui,sans-serif}
.card{background:#121c17;border:1px solid #22312a;border-radius:20px;padding:28px 24px;width:min(420px,96vw)}
h1{margin:0 0 6px;font-size:1.3rem;color:#19b985}
p{margin:0 0 20px;color:#8ea39a;font-size:.9rem}
label{display:block;margin-bottom:12px;font-weight:600;font-size:.9rem}
input,select{display:block;width:100%;margin-top:5px;padding:12px;border-radius:12px;
  border:1.5px solid #22312a;background:#0e1713;color:#e8f1ed;font:inherit}
input:focus,select:focus{outline:0;border-color:#19b985}
button{width:100%;margin-top:8px;padding:13px;border:0;border-radius:14px;
  background:linear-gradient(135deg,#0f8a64,#0b3d2e);color:#fff;font:700 1rem inherit;cursor:pointer}
button:hover{opacity:.9}
#msg{margin-top:12px;padding:10px 14px;border-radius:12px;display:none;font-size:.9rem}
.ok{background:color-mix(in srgb,#19b985 15%,transparent);color:#19b985;border:1px solid #19b985}
.err{background:color-mix(in srgb,#d6453d 15%,transparent);color:#d6453d;border:1px solid #d6453d}
</style>
</head>
<body>
<div class="card">
  <h1>🔐 Admin Setup</h1>
  <p>Set the first admin user. Requires the <code>ADMIN_SETUP_TOKEN</code> from Render env vars.</p>
  <form id="f">
    <label>Setup Token (from Render env vars)
      <input type="password" name="token" required placeholder="ADMIN_SETUP_TOKEN value">
    </label>
    <label>Firebase User UID
      <input type="text" name="uid" required placeholder="e.g. abc123XYZ...">
    </label>
    <label>Role
      <select name="role">
        <option value="super">Super Admin (full access)</option>
        <option value="operator">Operator (users + settings)</option>
        <option value="viewer">Viewer (read only)</option>
      </select>
    </label>
    <button type="submit">Grant Admin Access</button>
  </form>
  <div id="msg"></div>
</div>
<script>
document.getElementById('f').addEventListener('submit', function(e){
  e.preventDefault();
  var d={token:this.token.value,uid:this.uid.value,role:this.role.value};
  fetch('/api/admin/setup.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(d)})
  .then(function(r){return r.json();}).then(function(j){
    var m=document.getElementById('msg');
    m.style.display='block';
    if(j.success){m.className='ok';m.textContent='✓ '+j.message;}
    else{m.className='err';m.textContent='✗ '+(j.message||JSON.stringify(j));}
  }).catch(function(e){
    var m=document.getElementById('msg');m.style.display='block';m.className='err';m.textContent='Request failed: '+e;
  });
});
</script>
</body>
</html>
<?php
    exit;
}

// POST: perform the action
header('Content-Type: application/json');

try {
    $raw  = (string)file_get_contents('php://input');
    $data = json_decode($raw, true) ?: [];

    $token = trim((string)($data['token'] ?? ''));
    $uid   = trim((string)($data['uid']   ?? ''));
    $role  = trim((string)($data['role']  ?? 'super'));

    if ($setupToken === '') {
        echo json_encode(['success' => false, 'message' => 'ADMIN_SETUP_TOKEN env var is not set. Add it in Render dashboard.']);
        exit;
    }

    if (!hash_equals($setupToken, $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid setup token.']);
        exit;
    }

    if ($uid === '') {
        echo json_encode(['success' => false, 'message' => 'UID is required.']);
        exit;
    }

    $validRoles = ['viewer', 'operator', 'super'];
    if (!in_array($role, $validRoles, true)) {
        $role = 'super';
    }

    $db  = Firebase::db();
    $ref = $db->collection('admin_users')->document($uid);

    $ref->set([
        'role'      => $role,
        'active'    => true,
        'createdAt' => (int)(microtime(true) * 1000),
        'note'      => 'Created via web setup endpoint',
    ]);

    echo json_encode(['success' => true, 'message' => "Admin created! UID: $uid, Role: $role. You can now log in and go to /admin.php"]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
