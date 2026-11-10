<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\Request;
use App\Services\AuthService;

Api::handle(fn() => AuthService::resetPassword(Request::json()));
