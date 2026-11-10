<?php
declare(strict_types=1);

// Usage (Render Shell or local): php scripts/make-admin.php admin@example.com [viewer|operator|super]
// The person must already have a registered, e-mail-verified account. This is the ONLY way to create an admin.
require dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Firebase;

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$email = strtolower(trim($argv[1] ?? ''));
$role = $argv[2] ?? 'viewer';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['viewer', 'operator', 'super'], true)) {
    fwrite(STDERR, "Usage: php scripts/make-admin.php email [viewer|operator|super]\n"); exit(1);
}
$user = Firebase::auth()->getUserByEmail($email);
Firebase::db()->collection('admin_users')->document($user->uid)->set([
    'email' => $email, 'role' => $role, 'permissions' => [], 'active' => true, 'createdAtMs' => (int)(microtime(true) * 1000),
]);
echo "OK: {$email} is now admin ({$role})\n";
