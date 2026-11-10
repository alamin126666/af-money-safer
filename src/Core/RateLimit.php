<?php
declare(strict_types=1);

namespace App\Core;

use Google\Cloud\Firestore\Transaction;

/** Fixed-window counters in Firestore (works across Render instances). */
final class RateLimit
{
    private static function ref(string $key)
    {
        return Firebase::db()->collection('rate_limits')->document(hash('sha256', $key));
    }

    /** Count one event. Returns [count, retryAfterSeconds]. */
    public static function record(string $key, int $window): array
    {
        $ref = self::ref($key);
        $out = [0, 0];
        Firebase::db()->runTransaction(function (Transaction $t) use ($ref, $window, &$out) {
            $now = time();
            $s = $t->snapshot($ref);
            $d = $s->exists() ? $s->data() : null;
            if ($d === null || (int)$d['windowStart'] + $window <= $now) {
                $d = ['count' => 0, 'windowStart' => $now];
            }
            $d['count'] = (int)$d['count'] + 1;
            $d['expiresAt'] = (int)$d['windowStart'] + $window;
            $t->set($ref, $d);
            $out = [$d['count'], max(1, $d['expiresAt'] - $now)];
        });
        return $out;
    }

    /** Count and throw RATE_LIMITED when above $max. */
    public static function hit(string $key, int $max, int $window): void
    {
        [$count, $retry] = self::record($key, $window);
        if ($count > $max) {
            throw new ApiException('RATE_LIMITED', 429, ['retryAfter' => $retry]);
        }
    }

    /** Read-only check used before counting failures (e.g. wrong passwords). */
    public static function assertNotBlocked(string $key, int $max): void
    {
        $s = self::ref($key)->snapshot();
        if ($s->exists()) {
            $d = $s->data();
            $now = time();
            if ((int)$d['expiresAt'] > $now && (int)$d['count'] >= $max) {
                throw new ApiException('RATE_LIMITED', 429, ['retryAfter' => (int)$d['expiresAt'] - $now]);
            }
        }
    }

    public static function clear(string $key): void
    {
        self::ref($key)->delete();
    }
}
