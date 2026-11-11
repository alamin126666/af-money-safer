<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';
use App\Core\Page;
Page::render('admin/transactions', 'Transactions', ['auth' => true, 'admin' => true, 'layout' => 'admin', 'js' => ['pages/admin/common']]);
