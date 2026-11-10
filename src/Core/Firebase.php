<?php
declare(strict_types=1);

namespace App\Core;

use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Factory;

final class Firebase
{
    private static ?Factory $factory = null;
    private static ?object $db = null;
    private static ?Auth $auth = null;

    private static function factory(): Factory
    {
        if (self::$factory === null) {
            // Accepts the raw service-account JSON (starts with "{") or its base64 encoding.
            $raw = trim((string)Config::get('firebase.credentials_b64'));
            $json = str_starts_with($raw, '{') ? $raw : base64_decode($raw, true);
            $cred = is_string($json) ? json_decode($json, true) : null;
            if (!is_array($cred) || empty($cred['private_key'])) {
                throw new \RuntimeException('Firebase service account is not configured correctly');
            }
            self::$factory = (new Factory())->withServiceAccount($cred);
        }
        return self::$factory;
    }

    /** @return \Google\Cloud\Firestore\FirestoreClient */
    public static function db(): object
    {
        return self::$db ??= self::factory()->createFirestore()->database();
    }

    /** Test hook: inject an in-memory Firestore double. */
    public static function useDb(?object $db): void { self::$db = $db; }

    public static function auth(): Auth
    {
        return self::$auth ??= self::factory()->createAuth();
    }
}
