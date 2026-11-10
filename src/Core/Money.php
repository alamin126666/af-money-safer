<?php
declare(strict_types=1);

namespace App\Core;

/** Money is stored as integer poisha (1 BDT = 100). No floats touch balances. */
final class Money
{
    public static function toPoisha(int $taka): int { return $taka * 100; }

    /** "123", "123.4", "123.45" -> poisha. Anything else -> null. */
    public static function parseDecimal(string $s): ?int
    {
        if (!preg_match('/^(\d{1,10})(?:\.(\d{1,2}))?$/', trim($s), $m)) { return null; }
        $frac = isset($m[2]) ? str_pad($m[2], 2, '0') : '00';
        return (int)$m[1] * 100 + (int)$frac;
    }

    public static function fmt(int $p): string
    {
        $neg = $p < 0; $p = abs($p);
        return ($neg ? '-' : '') . number_format(intdiv($p, 100)) . '.' . str_pad((string)($p % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Fee rounded up to the next poisha. */
    public static function percent(int $p, float $pct): int
    {
        return $pct <= 0 ? 0 : (int)ceil($p * $pct / 100 - 1e-9);
    }

    /** Amount string for the gateway: whole taka as "100", otherwise "100.50". */
    public static function toApi(int $p): string
    {
        return $p % 100 === 0 ? (string)intdiv($p, 100) : number_format($p / 100, 2, '.', '');
    }
}
