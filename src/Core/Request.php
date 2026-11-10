<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    private static ?array $json = null;

    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $v = $_SERVER[$key] ?? null;
        return is_string($v) ? trim($v) : null;
    }

    /** JSON body as array. Only application/json, max 64 KB. */
    public static function json(): array
    {
        if (self::$json !== null) {
            return self::$json;
        }
        $ct = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
        if (!str_starts_with($ct, 'application/json')) {
            throw new ApiException('BAD_REQUEST', 415);
        }
        $raw = file_get_contents('php://input', false, null, 0, 65537);
        if ($raw === false || strlen($raw) > 65536) {
            throw new ApiException('BAD_REQUEST', 413);
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new ApiException('BAD_REQUEST', 400);
        }
        return self::$json = $data;
    }

    public static function str(string $key, int $max = 256): string
    {
        $v = self::json()[$key] ?? '';
        return is_string($v) ? mb_substr($v, 0, $max) : '';
    }

    public static function ip(): string
    {
        $h = (string)Config::get('app.client_ip_header', '');
        $candidate = $h !== '' ? self::header($h) : null;
        if ($candidate !== null) {
            $candidate = trim(explode(',', $candidate)[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }
        return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public static function ipHash(): string
    {
        return hash_hmac('sha256', self::ip(), (string)Config::get('app.secret'));
    }
}
