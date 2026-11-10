<?php
declare(strict_types=1);
putenv('APP_SECRET=' . str_repeat('a', 64)); $_ENV['APP_SECRET'] = str_repeat('a', 64);
require dirname(__DIR__) . '/vendor/autoload.php';
use App\Core\{Validator, Lang, Config};
use App\Services\OtpService;
use App\Mail\Mailer;

$fail = 0;
function t(string $name, bool $ok): void { global $fail; echo ($ok ? "PASS " : "FAIL ") . $name . "\n"; if (!$ok) $fail++; }

// Validator
t('email ok', Validator::email(' Test@Example.COM ') === 'test@example.com');
t('email bad', Validator::email('a@b') === null && Validator::email('x') === null);
t('name bn', Validator::name('রহিম উদ্দিন') === 'রহিম উদ্দিন');
t('name en', Validator::name("  John   O'Neil ") === "John O'Neil");
t('name bad', Validator::name('<script>') === null && Validator::name('A') === null);
t('pw strong', Validator::passwordStrong('Abcdef12'));
t('pw weak', !Validator::passwordStrong('abcdefgh') && !Validator::passwordStrong('Ab1') && !Validator::passwordStrong('ABCDEFG1'));
t('pw contains email local', !Validator::passwordStrong('Rahim1234X', 'rahim1234@x.com') === true);

// Lang parity + every error/success code used in src exists in both languages
$bn = require dirname(__DIR__) . '/lang/bn.php'; $en = require dirname(__DIR__) . '/lang/en.php';
t('lang keys equal', array_keys($bn) === array_keys($en));
$src = '';
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/src')) as $f) { if ($f->isFile()) $src .= file_get_contents((string)$f); }
preg_match_all("/ApiException\('([A-Z_]+)'/", $src, $m1);
preg_match_all("/\\['([A-Z_]+_(?:SENT|OK|VERIFIED|RESENT|RESET))'/", $src, $m2);
preg_match_all("/'(FIELD_[A-Z_]+)'/", $src, $m3);
$used = array_unique(array_merge($m1[1], $m2[1], $m3[1], ['OTP_INVALID','OTP_EXPIRED','OTP_LOCKED','OTP_USED']));
$missing = array_filter($used, fn($k) => !isset($bn[$k]) || !isset($en[$k]));
t('all codes translated (' . count($used) . ')', !$missing); if ($missing) echo '  missing: ' . implode(',', $missing) . "\n";
Lang::set('bn'); t('Lang bn', Lang::t('LOGIN_OK') === $bn['LOGIN_OK']);
t('Lang param', str_contains(Lang::t('mail.expires', ['min' => 5], 'en'), '5 minutes'));

// OTP MAC: bound to id + purpose + code
$a = OtpService::mac('id1', 'login', '123456');
t('mac stable', $a === OtpService::mac('id1', 'login', '123456'));
t('mac differs by code/purpose/id', $a !== OtpService::mac('id1', 'login', '123457') && $a !== OtpService::mac('id1', 'reset', '123456') && $a !== OtpService::mac('id2', 'login', '123456'));

// Email templates render and escape
foreach (['en', 'bn'] as $lang) {
    $vars = ['lang' => $lang, 'appName' => 'AF MONEY SAFER', 'title' => 'T<>', 'intro' => 'I', 'code' => '042917', 'expires' => 'E',
             'footer' => 'F', 'notice' => 'N'];
    extract($vars);
    ob_start(); require dirname(__DIR__) . '/templates/emails/otp.php'; $h = ob_get_clean();
    t("otp template $lang", str_contains($h, '042917') && str_contains($h, 'T&lt;&gt;') && str_contains($h, '</html>'));
}
t('mask email', \App\Services\AuthService::maskEmail('rahim@gmail.com') === 'ra***@gmail.com');
echo $fail ? "\n$fail FAILED\n" : "\nALL PASSED\n"; exit($fail ? 1 : 0);
