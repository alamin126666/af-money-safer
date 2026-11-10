<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Logger;

final class TashPayUnreachable extends \RuntimeException {}

/**
 * TashPay client. Follows tashpay-API接入文档.docx only:
 * JSON POST, header `account`, ms `timestamp`, MD5 `sign` over sorted params + "&key=API_KEY".
 */
final class TashPay
{
    /** Codes that mean "TashPay definitely did not accept this withdrawal". 5007 (duplicate) and 500 are NOT here. */
    public const WITHDRAW_REJECTED = [5001, 5002, 5003, 5004, 5005, 5006, 5009, 5010, 5011, 5012, 5014, 5015, 5016, 5023];

    public static function sign(array $params, string $apiKey): string
    {
        $p = [];
        foreach ($params as $k => $v) {
            if ($k === 'sign' || $v === null || is_array($v) || is_object($v)) { continue; }
            $s = is_bool($v) ? ($v ? 'true' : 'false') : (string)$v;
            if (preg_match('/^[\s\x{00A0}\x{3000}]*$/u', $s)) { continue; }       // blank / invisible-only values
            $p[(string)$k] = $s;
        }
        ksort($p, SORT_STRING);
        $s1 = implode('&', array_map(static fn($k, $v) => "{$k}={$v}", array_keys($p), $p));
        return strtolower(md5($s1 . '&key=' . $apiKey));
    }

    /** Verifies sign and merchant account of a callback payload. */
    public static function verifyCallback(array $payload): bool
    {
        $key = (string)Config::get('tashpay.api_key', '');
        $acc = (string)Config::get('tashpay.account', '');
        if ($key === '' || $acc === '' || !isset($payload['sign']) || !is_string($payload['sign'])) { return false; }
        if (!hash_equals($acc, (string)($payload['account'] ?? ''))) { return false; }
        return hash_equals(self::sign($payload, $key), strtolower($payload['sign']));
    }

    /** @return array{code:int,msg:string,data:?array} @throws TashPayUnreachable when the outcome is unknown */
    public static function post(string $path, array $body): array
    {
        $cfg = Config::get('tashpay');
        if (empty($cfg['account']) || empty($cfg['api_key'])) {
            throw new \RuntimeException('TashPay credentials missing');
        }
        $body['timestamp'] = (int)(microtime(true) * 1000);
        $body['sign'] = self::sign($body, (string)$cfg['api_key']);

        $ch = curl_init($cfg['base_url'] . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'account: ' . $cfg['account']],
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int)$cfg['timeout'], CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $raw = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $j = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($j) || !array_key_exists('code', $j)) {
            Logger::error('tashpay.bad_response', ['path' => $path, 'http' => $http, 'curl' => $err]);
            throw new TashPayUnreachable("TashPay {$path}: no valid response (HTTP {$http})");
        }
        $res = ['code' => (int)$j['code'], 'msg' => (string)($j['msg'] ?? ''), 'data' => is_array($j['data'] ?? null) ? $j['data'] : null];
        if ($res['code'] !== 0) {
            Logger::warn('tashpay.error', ['path' => $path, 'code' => $res['code'], 'msg' => $res['msg']]);
        }
        return $res;
    }
}
