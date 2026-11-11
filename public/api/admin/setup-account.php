<?php
declare(strict_types=1);

// One-time admin setup: creates the FIRST admin account and logs it in.
// After an admin exists this endpoint refuses with SETUP_CLOSED.
// bypassMaintenance: no admin exists yet, so the maintenance gate cannot be passed otherwise.
require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\Request;
use App\Services\AdminSetup;

Api::handle(fn() => AdminSetup::setup(Request::json()), ['bypassMaintenance' => true]);
