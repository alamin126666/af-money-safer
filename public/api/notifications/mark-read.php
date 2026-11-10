<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\Firebase;

Api::handle(function (array $u) {
    foreach (Firebase::db()->collection('notifications')->where('userId', '=', $u['uid'])->where('read', '=', false)->limit(100)->documents() as $d) {
        $d->reference()->update([['path' => 'read', 'value' => true]]);
    }
    return ['OK', []];
}, ['auth' => true]);
