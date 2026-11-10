<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\Money;
use App\Core\Request;
use App\Services\AdminGuard;
use App\Services\AuthService;
use App\Services\HistoryService;
use App\Services\PaymentService;
use App\Services\ProfileService;
use App\Services\WalletService;

Api::handle(fn($u) => ProfileService::updateName($u, Request::json()), ['auth' => true]);
