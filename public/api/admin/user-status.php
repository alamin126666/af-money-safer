<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\Request;
use App\Services\AdminGuard;
use App\Services\AdminService;

Api::handle(function ($u) { [$me] = AdminGuard::require('users'); return AdminService::setUserStatus($me, Request::json()); }, ['auth' => true]);
