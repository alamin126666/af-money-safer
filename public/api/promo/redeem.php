<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\Request;
use App\Services\PromoService;

Api::handle(fn($u) => PromoService::redeem($u, Request::json()), ['auth' => true]);
