<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\Core\Session;
use App\Core\Csrf;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\AdminGuard;

Session::start();

// Already logged in as admin → redirect to dashboard
if (!empty($_SESSION['uid']) && !empty($_SESSION['adminVerified'])) {
    header('Location: /hhhh/dashboard.php'); exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = Request::json();
    $email = trim((string)($data['email'] ?? ''));
    $pw    = (string)($data['password'] ?? '');
    $token = (string)($data['_csrf'] ?? '');

    if ($token === '' || !hash_equals(Csrf::token(), $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
        exit;
    }

    try {
        // Use Firebase Auth to verify password
        $key = (string)\App\Core\Config::get('firebase.web_api_key', '');
        $ch = curl_init('https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=' . rawurlencode($key));
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['email' => $email, 'password' => $pw, 'returnSecureToken' => false]),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $raw = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $j = json_decode((string)$raw, true) ?? [];

        if ($http !== 200 || empty($j['localId'])) {
            echo json_encode(['success' => false, 'message' => 'ইমেইল বা পাসওয়ার্ড ভুল।']); exit;
        }

        $uid = $j['localId'];
        $adminDoc = AdminGuard::adminDoc($uid);
        if (!$adminDoc) {
            echo json_encode(['success' => false, 'message' => 'আপনার Admin অ্যাক্সেস নেই।']); exit;
        }

        Session::regenerate();
        $_SESSION['uid'] = $uid;
        $_SESSION['authAt'] = time();
        $_SESSION['adminVerified'] = true;
        $_SESSION['adminRole'] = $adminDoc['role'] ?? 'viewer';
        echo json_encode(['success' => true, 'redirect' => '/hhhh/dashboard.php']); exit;
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'সার্ভার এরর। আবার চেষ্টা করুন।']); exit;
    }
}

$csrf = Csrf::token();
?>
<!DOCTYPE html>
<html lang="bn" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Admin Login — AF MONEY SAFER</title>
<meta name="robots" content="noindex,nofollow">
<style>
:root{--primary:#1a7a4a;--primary-dark:#14603a;--card:#fff;--bg:#f0f4f2;--text:#0f1f19;--muted:#6b7c74;--border:#d8e5de;--danger:#d6453d;--input:#f6f9f7;--r:16px;--shadow:0 4px 24px rgba(15,60,45,.10)}
[data-theme=dark]{--card:#1a2420;--bg:#0f1a16;--text:#e8f0ec;--muted:#8ba89a;--border:#2a3d35;--input:#1f2f29}
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100dvh;display:flex;align-items:center;justify-content:center;background:var(--bg);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:var(--text);padding:16px}
.card{background:var(--card);border-radius:24px;padding:36px 28px 32px;width:100%;max-width:400px;box-shadow:var(--shadow)}
.logo{display:flex;align-items:center;gap:10px;justify-content:center;margin-bottom:6px}
.logo-mark{width:48px;height:48px;background:var(--primary);border-radius:14px;display:flex;align-items:center;justify-content:center}
.logo-mark svg{color:#fff}
h1{text-align:center;font-size:1.3rem;font-weight:700;margin-bottom:4px}
.sub{text-align:center;color:var(--muted);font-size:.88rem;margin-bottom:28px}
.field{display:flex;flex-direction:column;gap:6px;margin-bottom:16px}
.field label{font-size:.88rem;font-weight:600;color:var(--muted)}
.inp-wrap{display:flex;align-items:center;background:var(--input);border:1.5px solid var(--border);border-radius:var(--r);overflow:hidden;transition:border-color .15s}
.inp-wrap:focus-within{border-color:var(--primary)}
.inp-wrap svg{flex-shrink:0;margin:0 12px;color:var(--muted)}
.inp-wrap input{flex:1;border:none;background:transparent;padding:13px 12px 13px 0;font-size:1rem;color:var(--text);outline:none}
.inp-wrap input::placeholder{color:var(--muted)}
.btn{width:100%;padding:14px;background:var(--primary);color:#fff;border:none;border-radius:var(--r);font-size:1rem;font-weight:700;cursor:pointer;transition:background .15s,transform .1s;margin-top:8px}
.btn:hover{background:var(--primary-dark)}
.btn:active{transform:scale(.98)}
.btn:disabled{opacity:.6;cursor:not-allowed;transform:none}
.err{color:var(--danger);font-size:.88rem;text-align:center;min-height:20px;margin-top:8px}
.theme-btn{position:fixed;top:14px;right:14px;background:var(--card);border:1.5px solid var(--border);border-radius:50%;width:40px;height:40px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--text)}
.admin-badge{display:inline-flex;align-items:center;gap:5px;background:color-mix(in srgb,var(--primary) 12%,transparent);color:var(--primary);border-radius:20px;padding:4px 12px;font-size:.8rem;font-weight:700;margin:0 auto 20px;width:fit-content}
</style>
</head>
<body>
<button class="theme-btn" id="themeBtn" title="থিম পরিবর্তন">
  <svg id="themeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
</button>
<div class="card">
  <div class="logo">
    <div class="logo-mark"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
  </div>
  <h1>AF MONEY SAFER</h1>
  <p class="sub">Admin Panel</p>
  <div class="admin-badge">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    Restricted Access
  </div>
  <form id="f" novalidate autocomplete="on">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
    <div class="field">
      <label>ইমেইল</label>
      <div class="inp-wrap">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
        <input type="email" name="email" autocomplete="email" placeholder="admin@example.com" required>
      </div>
    </div>
    <div class="field">
      <label>পাসওয়ার্ড</label>
      <div class="inp-wrap">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <input type="password" name="password" autocomplete="current-password" placeholder="••••••••" required>
      </div>
    </div>
    <button class="btn" id="btn" type="submit">লগইন করুন</button>
    <p class="err" id="err"></p>
  </form>
</div>
<script>
(function(){
  var html=document.documentElement;
  try{var t=localStorage.getItem('af_theme');if(t){html.setAttribute('data-theme',t);}}catch(e){}
  document.getElementById('themeBtn').addEventListener('click',function(){
    var n=html.getAttribute('data-theme')==='dark'?'light':'dark';
    html.setAttribute('data-theme',n);try{localStorage.setItem('af_theme',n);}catch(e){}
    document.getElementById('themeIcon').innerHTML=n==='dark'?'<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>':'<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>';
  });
  document.getElementById('f').addEventListener('submit',function(e){
    e.preventDefault();
    var btn=document.getElementById('btn'),err=document.getElementById('err');
    var email=this.email.value.trim(),pw=this.password.value,csrf=this._csrf.value;
    if(!email||!pw){err.textContent='সব ঘর পূরণ করুন।';return;}
    btn.disabled=true;btn.textContent='লগইন হচ্ছে...';err.textContent='';
    fetch('/hhhh/login.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email:email,password:pw,_csrf:csrf})})
      .then(function(r){return r.json();})
      .then(function(j){
        if(j.success){location.href=j.redirect;}
        else{err.textContent=j.message||'লগইন ব্যর্থ।';btn.disabled=false;btn.textContent='লগইন করুন';}
      }).catch(function(){err.textContent='নেটওয়ার্ক এরর।';btn.disabled=false;btn.textContent='লগইন করুন';});
  });
})();
</script>
</body>
</html>
