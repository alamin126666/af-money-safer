<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Services\HistoryService;

Api::handle(fn($u) => HistoryService::chart($u['uid']), ['methods' => ['GET'], 'auth' => true]);
