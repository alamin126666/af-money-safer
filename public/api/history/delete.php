<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\Request;
use App\Services\AuthService;
use App\Core\Firebase;

/**
 * Delete (hide) a transaction from the user's history view.
 * Only the owner can delete; we soft-delete by setting hiddenByUser = true.
 * Only pending/cancelled/failed transactions can be deleted (not successful ones).
 */
Api::handle(function ($u) {
    $data  = Request::json();
    $txId  = trim((string)($data['txId'] ?? ''));
    if ($txId === '') {
        return ['error' => 'tx_id_required', 'message' => 'Transaction ID required.'];
    }

    $db  = Firebase::db();
    $ref = $db->collection('transactions')->document($txId);
    $snap = $ref->snapshot();

    if (!$snap->exists()) {
        return ['error' => 'not_found', 'message' => 'Transaction not found.'];
    }

    $d = $snap->data();

    // Ownership check
    if (($d['userId'] ?? '') !== $u['uid']) {
        return ['error' => 'forbidden', 'message' => 'Not your transaction.'];
    }

    // Only allow deleting non-successful transactions
    $deletable = ['pending', 'cancelled', 'failed'];
    if (!in_array($d['status'] ?? '', $deletable, true)) {
        return ['error' => 'not_deletable', 'message' => 'সফল লেনদেন মুছে ফেলা যাবে না।'];
    }

    // Soft delete — mark hidden, don't actually remove from Firestore
    $ref->update([
        ['path' => 'hiddenByUser', 'value' => true],
        ['path' => 'hiddenAtMs',   'value' => (int)(microtime(true) * 1000)],
    ]);

    return 'Transaction removed from history.';
}, ['auth' => true, 'method' => 'POST']);
