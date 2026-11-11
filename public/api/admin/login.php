<?php
declare(strict_types=1);

// Admin login: email + password only — NO email verification code (OTP).
// Only accounts with an active admin_users doc may log in here.
require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\Request;
use App\Services\AuthService;

Api::handle(fn() => AuthService::adminLogin(Request::json()));
