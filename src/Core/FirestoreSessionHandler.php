<?php
declare(strict_types=1);

namespace App\Core;

/**
 * PHP sessions in Firestore (Render's disk is ephemeral and may run several instances).
 * Doc id = sha256(session id), so a Firestore leak does not expose usable session ids.
 * Writes are skipped when nothing changed in the last 60 s (saves latency + cost).
 */
final class FirestoreSessionHandler implements \SessionHandlerInterface, \SessionUpdateTimestampHandlerInterface
{
    private array $cache = [];

    public function __construct(private readonly int $lifetime) {}

    private function ref(string $id)
    {
        return Firebase::db()->collection('sessions')->document(hash('sha256', $id));
    }

    private function load(string $id): ?array
    {
        if (array_key_exists($id, $this->cache)) {
            return $this->cache[$id];
        }
        try {
            $snap = $this->ref($id)->snapshot();
            $d = $snap->exists() ? $snap->data() : null;
            if ($d !== null && (int)($d['expiresAt'] ?? 0) < time()) {
                $d = null;
            }
        } catch (\Throwable $e) {
            Logger::error('session.read_failed', ['e' => $e->getMessage()]);
            $d = null;
        }
        return $this->cache[$id] = $d;
    }

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function validateId(string $id): bool
    {
        return $this->load($id) !== null;
    }

    public function read(string $id): string|false
    {
        return (string)($this->load($id)['data'] ?? '');
    }

    public function write(string $id, string $data): bool
    {
        $old = $this->load($id);
        $now = time();
        if ($old !== null && ($old['data'] ?? null) === $data && $now - (int)($old['updatedAt'] ?? 0) < 60) {
            return true;
        }
        try {
            $this->ref($id)->set([
                'data'      => $data,
                'userId'    => $_SESSION['uid'] ?? null,
                'expiresAt' => $now + $this->lifetime,
                'updatedAt' => $now,
            ]);
            return true;
        } catch (\Throwable $e) {
            Logger::error('session.write_failed', ['e' => $e->getMessage()]);
            return false;
        }
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        return $this->write($id, $data);
    }

    public function destroy(string $id): bool
    {
        try { $this->ref($id)->delete(); } catch (\Throwable $e) { Logger::error('session.destroy_failed', ['e' => $e->getMessage()]); }
        unset($this->cache[$id]);
        return true;
    }

    public function gc(int $max_lifetime): int|false { return 0; } // cleaned by cron (scripts/cleanup.php)
}
