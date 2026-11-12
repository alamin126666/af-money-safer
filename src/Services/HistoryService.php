<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Firebase;
use App\Core\Time;

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
                // Skip soft-deleted (hidden by user)
                if (!empty($d['hiddenByUser'])) { continue; }
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

    /**
     * Last 14 days of the user's successful deposits/withdraws, aggregated
     * per day in Bangladesh time. Same single-index query as page() (userId
     * + createdAtMs), aggregated in PHP — no new index.
     * @return array{days:array}
     */
    public static function chart(string $uid): array
    {
        $now = Time::ms();
        $start = $now - 14 * 86400000;
        $dayMs = 86400000;
        $firstDay = (int)floor(($start + 21600000) / $dayMs);
        $lastDay = (int)floor(($now + 21600000) / $dayMs);
        $days = [];
        for ($d = $firstDay; $d <= $lastDay; $d++) {
            $key = gmdate('Y-m-d', $d * 86400);   // BD date of day $d (d counts days in BD time)
            $days[$key] = ['date' => $key, 'deposit' => 0, 'withdraw' => 0];
        }
        foreach (Firebase::db()->collection('transactions')->where('userId', '=', $uid)->orderBy('createdAtMs', 'DESC')->limit(500)->documents() as $doc) {
            $x = $doc->data();
            $ms = (int)($x['createdAtMs'] ?? 0);
            if ($ms < $start) { break; }   // DESC order: everything after this is older
            if (($x['status'] ?? '') !== 'successful') { continue; }
            $key = gmdate('Y-m-d', (int)($ms / 1000) + 21600);
            if (!isset($days[$key])) { continue; }
            if (($x['type'] ?? '') === 'deposit') { $days[$key]['deposit'] += (int)($x['creditedAmount'] ?? $x['amount']); }
            elseif (($x['type'] ?? '') === 'withdraw') { $days[$key]['withdraw'] += (int)$x['amount']; }
        }
        return ['OK', ['days' => array_values($days)]];
    }
}
