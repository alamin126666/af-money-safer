<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Firebase;
use App\Core\Logger;
use App\Core\Time;

/** In-app notifications. The browser translates `key` using its own language dictionary. */
final class Notifier
{
    public static function push(string $uid, string $key, array $params = []): void
    {
        try {
            Firebase::db()->collection('notifications')->add([
                'userId' => $uid, 'key' => $key, 'params' => $params, 'read' => false, 'createdAtMs' => Time::ms(),
            ]);
        } catch (\Throwable $e) {
            Logger::error('notify.failed', ['key' => $key, 'e' => $e->getMessage()]);
        }
    }

    /**
     * Send a notification to every user. Uses ONLY calls already proven live
     * on this site: the bounded select() read from AdminService::overview()
     * and the per-doc add() from Notifier::push(). The previous version used
     * offset() paging + WriteBatch — the only two Firestore calls in the
     * whole codebase that nothing else uses — and the send died with
     * INTERNAL_ERROR on the live site.
     */
    public static function broadcast(string $title, string $message): int
    {
        $db = Firebase::db();
        $sent = 0;
        foreach ($db->collection('users')->select(['fullName'])->limit(5000)->documents() as $d) {
            $db->collection('notifications')->add([
                'userId' => $d->id(), 'key' => 'n.broadcast',
                'params' => ['title' => $title, 'message' => $message],
                'read' => false, 'createdAtMs' => Time::ms(),
            ]);
            $sent++;
        }
        return $sent;
    }
}
