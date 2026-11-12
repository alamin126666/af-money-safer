<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\Request;
use App\Services\AdminGuard;
use App\Services\PaymentService;

Api::handle(function ($u) {
    [$me] = AdminGuard::require('balance');   // approving a withdraw releases money — super only
    return PaymentService::decideWithdraw($me, Request::json());
}, ['auth' => true]);
