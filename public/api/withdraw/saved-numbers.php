<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/src/bootstrap.php';

use App\Core\Api;
use App\Core\Request;
use App\Services\SavedNumbersService;

Api::handle(function ($u) {
    $m = Request::method();
    if ($m === 'GET') { return SavedNumbersService::list($u); }
    $in = Request::json();
    if ($m === 'DELETE') { return SavedNumbersService::remove($u, $in); }
    return SavedNumbersService::add($u, $in);
}, ['methods' => ['GET', 'POST', 'DELETE'], 'auth' => true]);
