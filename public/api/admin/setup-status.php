<?php
declare(strict_types=1);

// Public: has the one-time admin setup already been done?
// GET /api/admin/setup-status.php -> { "setupDone": true|false }
require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Services\AdminSetup;

Api::handle(fn() => ['OK', ['setupDone' => AdminSetup::alreadySetup()]], ['methods' => ['GET'], 'csrf' => false, 'bypassMaintenance' => true]);
