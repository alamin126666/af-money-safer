<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Firebase;
use App\Core\Logger;
use App\Core\Request;
use Google\Cloud\Firestore\FieldValue;

final class AuditLog
{
    /** Append-only. Never put secrets, passwords or OTP codes in $meta. */
    public static function log(string $actor, string $action, string $target, array $meta = []): void
    {
        try {
            Firebase::db()->collection('audit_logs')->add([
                'actor' => $actor, 'action' => $action, 'target' => $target, 'meta' => $meta,
                'ipHash' => Request::ipHash(), 'createdAt' => FieldValue::serverTimestamp(),
            ]);
        } catch (\Throwable $e) {
            Logger::error('audit.write_failed', ['action' => $action, 'e' => $e->getMessage()]);
        }
    }
}
