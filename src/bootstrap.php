<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Config;
use App\Core\Logger;

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

set_error_handler(static function (int $no, string $str, string $file, int $line): bool {
    Logger::error('php.error', ['no' => $no, 'm' => $str, 'at' => basename($file) . ':' . $line]);
    return true; // never print to the user
});
set_exception_handler(static function (\Throwable $e): void {
    Logger::error('php.uncaught', ['e' => get_class($e), 'm' => $e->getMessage()]);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo '{"success":false,"code":"INTERNAL_ERROR","message":"Internal error","data":{},"timestamp":' . time() . '}';
});

// Required secrets: fail closed.
if (strlen((string)Config::get('app.secret')) < 32) {
    throw new RuntimeException('APP_SECRET must be at least 32 characters');
}

// HTTPS (Render terminates TLS and sets X-Forwarded-Proto).
if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
    $_SERVER['HTTPS'] = 'on';
}
if (!Config::isLocal() && empty($_SERVER['HTTPS']) && PHP_SAPI !== 'cli') {
    header('Location: https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://challenges.cloudflare.com; "
    . "frame-src https://challenges.cloudflare.com; style-src 'self' 'unsafe-inline'; img-src 'self' data:; "
    . "connect-src 'self'; font-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
