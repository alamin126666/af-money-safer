<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Firebase;

final class HistoryService
{
    /** Date (YYYY-MM-DD) in Bangladesh time -> ms. */
    private static function day(string $d, bool $end): ?int
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) { return null; }
        $t = strtotime($d . ($end ? ' 23:59:59' : ' 00:00:00') . ' +06:00');
        return $t === false ? null : $t * 1000 + ($end ? 999 : 0);
    }

    /**
     * Only one composite index is needed (userId + createdAtMs): type/method/status are filtered here
     * while scanning, which keeps the index list short for a per-user history.
     * @return array{items:array,next:?int}
     */
    public static function page(string $uid, array $f, int $limit = 15): array
    {
        $limit = max(1, min(30, $limit));
        $base = Firebase::db()->collection('transactions')->where('userId', '=', $uid);
        $s = self::day((string)($f['startDate'] ?? ''), false);
        $e = self::day((string)($f['endDate'] ?? ''), true);
        if ($s !== null) { $base = $base->where('createdAtMs', '>=', $s); }
        if ($e !== null) { $base = $base->where('createdAtMs', '<=', $e); }

        $type = in_array($f['type'] ?? '', ['deposit', 'withdraw'], true) ? $f['type'] : null;
        $method = in_array($f['method'] ?? '', ['bKash', 'Nagad'], true) ? $f['method'] : null;
        $status = in_array($f['status'] ?? '', ['pending', 'processing', 'successful', 'failed', 'cancelled'], true) ? $f['status'] : null;

        $cursor = isset($f['cursor']) && is_numeric($f['cursor']) ? (int)$f['cursor'] : null;
        $items = []; $scanned = 0; $more = false; $last = null;

        while (count($items) < $limit && $scanned < 250 && !$more) {
            $q = $base->orderBy('createdAtMs', 'DESC');
            if ($cursor !== null) { $q = $q->startAfter([$cursor]); }
            $n = 0;
            foreach ($q->limit(50)->documents() as $doc) {
                $n++; $scanned++;
                $d = $doc->data() + ['id' => $doc->id()];
                $cursor = $last = (int)$d['createdAtMs'];
                if (($type && $d['type'] !== $type) || ($method && $d['method'] !== $method) || ($status && $d['status'] !== $status)) { continue; }
                if (count($items) >= $limit) { $more = true; break; }
                $items[] = WalletService::publicTx($d);
            }
            if ($n < 50) { break; }                // reached the end of the user's history
            if ($n === 50 && count($items) >= $limit) { $more = true; }
        }
        $nextCursor = $more && $items ? $items[count($items) - 1]['createdAtMs'] : null;
        return ['items' => $items, 'next' => $nextCursor];
    }
}
