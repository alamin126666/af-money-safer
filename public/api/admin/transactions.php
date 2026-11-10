<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\Request;
use App\Services\AdminGuard;
use App\Services\AdminService;

Api::handle(function ($u) { AdminGuard::require('view'); $in = Request::json(); return !empty($in['detail']) ? AdminService::txDetail(['id' => $in['detail']]) : AdminService::transactions($in); }, ['auth' => true]);
