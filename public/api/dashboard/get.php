<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\Firebase;
use App\Services\HistoryService;
use App\Services\PaymentService;
use App\Services\ProfileService;
use App\Services\WalletService;

Api::handle(function (array $u) {
    $info = ProfileService::info($u);
    $unread = 0;
    foreach (Firebase::db()->collection('notifications')->where('userId', '=', $u['uid'])->where('read', '=', false)->limit(100)->documents() as $_) { $unread++; }
    return ['OK', [
        'user' => ['fullName' => $info['fullName'], 'email' => $info['email'], 'avatar' => $info['avatar']],
        'wallet' => WalletService::wallet($u['uid']),
        'unread' => $unread,
        'limits' => PaymentService::limits(),
        'recent' => HistoryService::page($u['uid'], [], 5)['items'],
    ]];
}, ['methods' => ['GET'], 'auth' => true]);
