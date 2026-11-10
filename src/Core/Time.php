<?php
declare(strict_types=1);

namespace App\Core;

final class Time
{
    public static function ms(): int { return (int)(microtime(true) * 1000); }
}
