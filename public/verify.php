<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

App\Core\Page::render('verify', 'Verify', ['js' => ['otp']]);
