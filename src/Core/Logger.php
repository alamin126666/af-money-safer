<?php
declare(strict_types=1);

namespace App\Core;

/** JSON lines to stderr (Render captures it). Never pass passwords, OTPs, tokens or API keys. */
final class Logger
{
    public static function log(string $level, string $event, array $ctx = []): void
    {
        error_log(json_encode(
            ['t' => gmdate('c'), 'level' => $level, 'event' => $event, 'ctx' => $ctx],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }

    public static function error(string $event, array $ctx = []): void { self::log('error', $event, $ctx); }
    public static function warn(string $event, array $ctx = []): void { self::log('warn', $event, $ctx); }
    public static function info(string $event, array $ctx = []): void { self::log('info', $event, $ctx); }
}
