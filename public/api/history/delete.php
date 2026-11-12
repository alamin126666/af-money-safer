<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\ApiException;
use App\Core\Request;
use App\Services\AuthService;
use App\Core\Firebase;

/**
 * Delete (hide) a transaction from the user's history view.
 * Only the owner can delete; we soft-delete by setting hiddenByUser = true.
 * Only pending/cancelled/failed transactions can be deleted (not successful ones).
 *
 * NOTE: the handler must return a [code, data] tuple (or throw ApiException) —
 * returning a plain string used to crash Response::send (typed array $data)
 * AFTER the delete had already happened, so the item was removed but the
 * app showed "Something went wrong" instead of success.
 */
Api::handle(function ($u) {
    $data  = Request::json();
    $txId  = trim((string)($data['txId'] ?? ''));
    if ($txId === '') {
        throw new ApiException('VALIDATION_FAILED', 422);
    }

    $db  = Firebase::db();
    $ref = $db->collection('transactions')->document($txId);
    $snap = $ref->snapshot();

    if (!$snap->exists()) {
        throw new ApiException('NOT_FOUND', 404);
    }

    $d = $snap->data();

    // Ownership check
    if (($d['userId'] ?? '') !== $u['uid']) {
        throw new ApiException('FORBIDDEN', 403);
    }

    // Only allow deleting non-successful transactions
    $deletable = ['pending', 'cancelled', 'failed'];
    if (!in_array($d['status'] ?? '', $deletable, true)) {
        throw new ApiException('NOT_DELETABLE', 422);
    }

    // Soft delete — mark hidden, don't actually remove from Firestore
    $ref->update([
        ['path' => 'hiddenByUser', 'value' => true],
        ['path' => 'hiddenAtMs',   'value' => (int)(microtime(true) * 1000)],
    ]);

    return ['TX_DELETED', []];
}, ['auth' => true, 'method' => 'POST']);
