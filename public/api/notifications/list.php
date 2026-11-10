<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\Firebase;

Api::handle(function (array $u) {
    $items = [];
    $q = Firebase::db()->collection('notifications')->where('userId', '=', $u['uid'])->orderBy('createdAtMs', 'DESC')->limit(40);
    foreach ($q->documents() as $d) {
        $x = $d->data();
        $items[] = ['id' => $d->id(), 'key' => $x['key'], 'params' => (object)($x['params'] ?? []), 'read' => (bool)$x['read'], 'createdAtMs' => (int)$x['createdAtMs']];
    }
    return ['OK', ['items' => $items]];
}, ['methods' => ['GET'], 'auth' => true]);
