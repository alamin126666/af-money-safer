<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

App\Core\Page::render('withdraw', 'Withdraw', ['auth' => true, 'app' => true, 'js' => ['otp']]);
