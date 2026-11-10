<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

App\Core\Page::render('deposit', 'Deposit', ['auth' => true, 'app' => true]);
