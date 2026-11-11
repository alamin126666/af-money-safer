<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';
use App\Core\Session;
use App\Services\AuthService;
Session::start();
AuthService::logout();
header('Location: /admin/login.php', true, 302);
exit;
