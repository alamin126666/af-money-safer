<?php
// TEMPORARY TEST FILE - DELETE AFTER USE
require dirname(__DIR__) . '/src/bootstrap.php';
use App\Core\Config;

$c = Config::get('smtp');
echo "<pre>";
echo "SMTP_HOST: " . ($c['host'] ?? 'NOT SET') . "\n";
echo "SMTP_PORT: " . ($c['port'] ?? 'NOT SET') . "\n";
echo "SMTP_USER: " . ($c['user'] ?? 'NOT SET') . "\n";
echo "SMTP_PASS: " . (empty($c['pass']) ? 'NOT SET' : 'SET (' . strlen((string)$c['pass']) . ' chars)') . "\n";
echo "SMTP_FROM: " . ($c['from_email'] ?? 'NOT SET') . "\n\n";

if (empty($c['host']) || empty($c['user']) || empty($c['pass']) || empty($c['from_email'])) {
    echo "❌ One or more SMTP values are missing!\n";
    exit;
}

try {
    $m = new PHPMailer\PHPMailer\PHPMailer(true);
    $m->isSMTP();
    $m->Host = $c['host'];
    $m->Port = $c['port'];
    $m->SMTPAuth = true;
    $m->Username = (string)$c['user'];
    $m->Password = (string)$c['pass'];
    $m->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $m->Timeout = 15;
    $m->setFrom($c['from_email'], 'AF MONEY SAFER');
    $m->addAddress((string)$c['user']);
    $m->Subject = 'SMTP Test';
    $m->Body = 'Test email from AF Money Safer';
    $m->send();
    echo "✅ Email sent successfully!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "</pre>";
