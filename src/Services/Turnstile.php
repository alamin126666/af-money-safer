<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Core\Config;
use App\Core\Logger;
use App\Core\Request;

final class Turnstile
{
    /** Fails closed: no secret, no token, network error or rejection => exception. */
    public static function verify(?string $token): void
    {
        $secret = (string)Config::get('turnstile.secret_key', '');
        if ($secret === '') {
            Logger::error('turnstile.not_configured');
            throw new ApiException('CAPTCHA_UNAVAILABLE', 503);
        }
        if ($token === null || $token === '' || strlen($token) > 2048) {
            throw new ApiException('CAPTCHA_FAILED', 400);
        }
        $ch = curl_init((string)Config::get('turnstile.verify_url'));
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query(['secret' => $secret, 'response' => $token, 'remoteip' => Request::ip()]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
        ]);
        $raw = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $j = is_string($raw) ? json_decode($raw, true) : null;
        if ($http !== 200 || !is_array($j)) {
            Logger::error('turnstile.unreachable', ['http' => $http]);
            throw new ApiException('CAPTCHA_UNAVAILABLE', 503);
        }
        if (($j['success'] ?? false) !== true) {
            throw new ApiException('CAPTCHA_FAILED', 400);
        }
    }
}
