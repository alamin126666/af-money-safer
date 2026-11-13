<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Core\Firebase;

/** Admin = a logged-in user that also has an active admin_users/{uid} document. */
final class AdminGuard
{
    private const ROLES = [
        'viewer'   => ['view'],
        'operator' => ['view', 'users', 'settings'],
        'super'    => ['view', 'users', 'settings', 'balance', 'delete'],
    ];

    public static function adminDoc(string $uid): ?array
    {
        $s = Firebase::db()->collection('admin_users')->document($uid)->snapshot();
        if (!$s->exists()) { return null; }
        $d = $s->data();
        return !empty($d['active']) ? $d : null;
    }

    public static function isAdmin(string $uid): bool { return self::adminDoc($uid) !== null; }

    public static function isAdminSession(): bool
    {
        $uid = $_SESSION['uid'] ?? null;
        if (!is_string($uid) || $uid === '') { return false; }
        $c = $_SESSION['isAdmin'] ?? null;
        if (is_array($c) && time() - (int)$c[1] < 60) { return (bool)$c[0]; }
        $v = self::isAdmin($uid);
        $_SESSION['isAdmin'] = [$v, time()];
        return $v;
    }

    /** @return array{0:array,1:array} [user, adminDoc] */
    public static function require(string $perm): array
    {
        $user = AuthService::requireUser();
        if (time() - (int)($_SESSION['authAt'] ?? 0) > 8 * 3600) {
            throw new ApiException('UNAUTHENTICATED', 401);
        }
        $a = self::adminDoc($user['uid']);
        if ($a === null) { throw new ApiException('FORBIDDEN', 403); }
        $allowed = array_merge(self::ROLES[$a['role'] ?? 'viewer'] ?? ['view'], (array)($a['permissions'] ?? []));
        if (!in_array($perm, $allowed, true)) { throw new ApiException('FORBIDDEN', 403); }
        return [$user, $a];
    }
}
