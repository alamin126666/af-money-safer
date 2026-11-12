<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Core\Firebase;
use App\Core\RateLimit;
use App\Core\Time;
use App\Core\Validator;

/**
 * Saved withdraw numbers: unlimited contacts per user (name + method +
 * wallet number). Scoped by userId on every operation; the list is read
 * with a single-field query and sorted in PHP, so no new composite
 * index is needed.
 */
final class SavedNumbersService
{
    private const METHODS = ['bKash', 'Nagad'];

    private static function col() { return Firebase::db()->collection('saved_numbers'); }

    /** Same wallet-number rule as PaymentService::normalizeWallet. */
    private static function normalizeWallet(mixed $v): string
    {
        $s = is_string($v) ? preg_replace('/[\s\-]/', '', $v) : '';
        if (!is_string($s) || !preg_match('/^(?:\+?88)?(01[3-9]\d{8})$/', $s, $m)) { throw new ApiException('INVALID_WALLET', 422); }
        return $m[1];
    }

    public static function list(array $user): array
    {
        $rows = [];
        foreach (self::col()->where('userId', '=', $user['uid'])->limit(200)->documents() as $d) {
            $x = $d->data();
            $rows[] = ['id' => $d->id(), 'name' => $x['name'], 'method' => $x['method'], 'walletNo' => $x['walletNo'],
                       'createdAtMs' => (int)($x['createdAtMs'] ?? 0)];
        }
        usort($rows, static fn($a, $b) => $b['createdAtMs'] <=> $a['createdAtMs']);
        return ['OK', ['items' => $rows]];
    }

    public static function add(array $user, array $in): array
    {
        RateLimit::hit("savednum:{$user['uid']}", 30, 3600);
        $name = Validator::name(is_string($in['name'] ?? null) ? $in['name'] : '');
        $method = is_string($in['method'] ?? null) ? $in['method'] : '';
        if ($name === null) { throw new ApiException('VALIDATION_FAILED', 422, ['fields' => ['name' => 'invalid']]); }
        if (!in_array($method, self::METHODS, true)) { throw new ApiException('INVALID_METHOD', 422); }
        $wallet = self::normalizeWallet($in['walletNo'] ?? '');
        $id = bin2hex(random_bytes(10));
        self::col()->document($id)->set([
            'userId' => $user['uid'], 'name' => $name, 'method' => $method, 'walletNo' => $wallet,
            'createdAtMs' => Time::ms(),
        ]);
        return ['SAVED_NUMBER_SAVED', ['id' => $id, 'name' => $name, 'method' => $method, 'walletNo' => $wallet]];
    }

    public static function update(array $user, array $in): array
    {
        $id = is_string($in['id'] ?? null) ? $in['id'] : '';
        if (!preg_match('/^[0-9a-f]{20}$/', $id)) { throw new ApiException('NOT_FOUND', 404); }
        $ref = self::col()->document($id);
        $s = $ref->snapshot();
        if (!$s->exists() || ($s->data()['userId'] ?? '') !== $user['uid']) { throw new ApiException('NOT_FOUND', 404); }
        RateLimit::hit("savednum:{$user['uid']}", 30, 3600);
        $name = Validator::name(is_string($in['name'] ?? null) ? $in['name'] : '');
        $method = is_string($in['method'] ?? null) ? $in['method'] : '';
        if ($name === null) { throw new ApiException('VALIDATION_FAILED', 422, ['fields' => ['name' => 'invalid']]); }
        if (!in_array($method, self::METHODS, true)) { throw new ApiException('INVALID_METHOD', 422); }
        $wallet = self::normalizeWallet($in['walletNo'] ?? '');
        $ref->set([
            'userId' => $user['uid'], 'name' => $name, 'method' => $method, 'walletNo' => $wallet,
            'createdAtMs' => (int)($s->data()['createdAtMs'] ?? Time::ms()),
        ]);
        return ['SAVED_NUMBER_UPDATED', ['id' => $id, 'name' => $name, 'method' => $method, 'walletNo' => $wallet]];
    }

    public static function remove(array $user, array $in): array
    {
        $id = is_string($in['id'] ?? null) ? $in['id'] : '';
        if (!preg_match('/^[0-9a-f]{20}$/', $id)) { throw new ApiException('NOT_FOUND', 404); }
        $ref = self::col()->document($id);
        $s = $ref->snapshot();
        if (!$s->exists() || ($s->data()['userId'] ?? '') !== $user['uid']) { throw new ApiException('NOT_FOUND', 404); }
        $ref->delete();
        return ['SAVED_NUMBER_DELETED', []];
    }
}
