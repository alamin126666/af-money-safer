<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Services\AdminGuard;
use App\Services\AdminService;

Api::handle(function ($u) {
    AdminGuard::require('settings');
    return AdminService::reports($_GET);
}, ['methods' => ['GET'], 'auth' => true]);
