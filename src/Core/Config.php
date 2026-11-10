<?php
declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static ?array $data = null;

    public static function all(): array
    {
        return self::$data ??= require dirname(__DIR__, 2) . '/config/config.php';
    }

    public static function get(string $path, mixed $default = null): mixed
    {
        $v = self::all();
        foreach (explode('.', $path) as $k) {
            if (!is_array($v) || !array_key_exists($k, $v)) {
                return $default;
            }
            $v = $v[$k];
        }
        return $v;
    }

    public static function isLocal(): bool
    {
        return Config::get('app.env') === 'local';
    }
}
