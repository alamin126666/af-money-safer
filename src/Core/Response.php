<?php
declare(strict_types=1);

namespace App\Core;

/** Every API answers with: {success, code, message, data, timestamp}. */
final class Response
{
    public static function send(bool $success, string $code, array $data = [], int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode([
            'success'   => $success,
            'code'      => $code,
            'message'   => Lang::t($code),
            'data'      => (object)$data,
            'timestamp' => time(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
