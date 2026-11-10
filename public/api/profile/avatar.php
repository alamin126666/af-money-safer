<?php
declare(strict_types=1);

// Streams the logged-in user's own uploaded avatar. No user id in the URL, so nobody else's photo can be requested.
require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Session;
use App\Services\AuthService;
use App\Services\ProfileService;

Session::start();
try {
    ProfileService::streamAvatar(AuthService::requireUser());
} catch (\Throwable) {
    http_response_code(401);
}
