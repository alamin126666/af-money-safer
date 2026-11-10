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
}
