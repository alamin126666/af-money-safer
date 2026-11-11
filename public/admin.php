<?php
declare(strict_types=1);
// The admin panel moved to /admin/dashboard.php — keep the old URL working.
header('Location: /admin/dashboard.php', true, 302);
exit;
