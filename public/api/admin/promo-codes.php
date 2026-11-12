<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\ApiException;
use App\Core\Request;
use App\Services\AdminGuard;
use App\Services\PromoService;

Api::handle(function ($u) {
    [$me] = AdminGuard::require('settings');
    if (Request::method() === 'GET') { return PromoService::list(); }
    $in = Request::json();
    $action = $in['action'] ?? '';
    if ($action === 'create') { return PromoService::create($me, $in); }
    if ($action === 'toggle') { return PromoService::toggle($me, $in); }
    if ($action === 'delete') { return PromoService::delete($me, $in); }
    throw new ApiException('BAD_REQUEST', 400);
}, ['methods' => ['GET', 'POST'], 'auth' => true]);
