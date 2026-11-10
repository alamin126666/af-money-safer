<?php
declare(strict_types=1);

namespace App\Core;

final class Lang
{
    private static string $lang = 'bn';
    private static array $cache = [];

    public static function init(): void
    {
        $langs = Config::get('app.languages');
        $want = null;
        $body = null;
        if (Request::method() !== 'GET' && str_starts_with(strtolower((string)($_SERVER['CONTENT_TYPE'] ?? '')), 'application/json')) {
            try { $body = Request::json()['lang'] ?? null; } catch (\Throwable) { $body = null; }
        }
        foreach ([$body, $_GET['lang'] ?? null, $_SESSION['lang'] ?? null, $_COOKIE['lang'] ?? null] as $c) {
            if (is_string($c) && in_array($c, $langs, true)) { $want = $c; break; }
        }
        self::set($want ?? (string)Config::get('app.default_lang'));
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['lang'] = self::$lang;
        }
    }

    public static function set(string $lang): void
    {
        self::$lang = in_array($lang, Config::get('app.languages'), true) ? $lang : 'bn';
    }

    public static function current(): string { return self::$lang; }

    public static function t(string $key, array $params = [], ?string $lang = null): string
    {
        $lang ??= self::$lang;
        $dict = self::$cache[$lang] ??= require dirname(__DIR__, 2) . "/lang/{$lang}.php";
        $s = $dict[$key] ?? (self::$cache['en'] ??= require dirname(__DIR__, 2) . '/lang/en.php')[$key] ?? $key;
        foreach ($params as $k => $v) {
            $s = str_replace('{' . $k . '}', (string)$v, $s);
        }
        return $s;
    }
}
