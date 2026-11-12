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
     * One notification per user (key n.broadcast, title + message in params).
     * Pages through every user in 500-user batches with 400-doc write batches.
     * @return int how many users were notified
     */
    public static function broadcast(string $title, string $message): int
    {
        $db = Firebase::db();
        $sent = 0;
        $offset = 0;
        while (true) {
            $docs = $db->collection('users')->select(['fullName'])->limit(500)->offset($offset)->documents();
            $batch = $db->batch();
            $n = 0;
            foreach ($docs as $d) {
                $batch->create($db->collection('notifications')->document(), [
                    'userId' => $d->id(), 'key' => 'n.broadcast',
                    'params' => ['title' => $title, 'message' => $message],
                    'read' => false, 'createdAtMs' => Time::ms(),
                ]);
                $n++;
                if ($n % 400 === 0) { $batch->commit(); $batch = $db->batch(); }
            }
            if ($n > 0) { $batch->commit(); }
            $sent += $n;
            if ($n < 500) { break; }
            $offset += 500;
        }
        return $sent;
    }
}
