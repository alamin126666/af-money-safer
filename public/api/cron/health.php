<?php
declare(strict_types=1);

// Render health check. Deliberately minimal: no Firebase call, no secrets, no stack traces.
header('Content-Type: application/json');
header('Cache-Control: no-store');
echo json_encode(['status' => 'ok', 'time' => time()]);
