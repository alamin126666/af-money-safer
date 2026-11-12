<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Services\PromoService;

Api::handle(fn($u) => PromoService::redemptions($u), ['methods' => ['GET'], 'auth' => true]);
