<?php
declare(strict_types=1);

/**
 * Central config. Secrets come ONLY from environment variables (Render dashboard / .env locally).
 * Nothing in this file is ever sent to the browser; public values are exposed
 * through App\Core\View::publicConfig() which whitelists safe keys.
 */

use Dotenv\Dotenv;

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (is_file(dirname(__DIR__) . '/.env')) {
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
}

function env(string $key, mixed $default = null): mixed
{
    $v = $_ENV[$key] ?? getenv($key);
    if ($v === false || $v === null || $v === '') {
        return $default;
    }
    return match (strtolower((string)$v)) {
        'true'  => true,
        'false' => false,
        default => $v,
    };
}

function required_env(string $key): string
{
    $v = env($key);
    if ($v === null) {
        throw new RuntimeException("Missing required environment variable: {$key}");
    }
    return (string)$v;
}

return [
    'app' => [
        'name'        => env('APP_NAME', 'AF MONEY SAFER'),
        'url'         => rtrim((string)env('APP_URL', ''), '/'),
        'env'         => env('APP_ENV', 'production'),
        'secret'      => env('APP_SECRET'),
        'client_ip_header' => env('CLIENT_IP_HEADER', 'CF-Connecting-IP'),
        'maintenance' => (bool)env('MAINTENANCE_MODE', false),
        'languages'   => ['bn', 'en'],
        'default_lang'=> 'bn',
        'themes'      => ['light', 'dark'],
        'default_theme' => 'light',
        'logo'        => '/assets/img/logo.svg',
    ],
    'session' => [
        'lifetime' => (int)env('SESSION_LIFETIME_MIN', 60) * 60,
        // Lax (not Strict): after the TashPay cashier redirects back, a Strict cookie would not be sent
        // and the user would look logged out. CSRF is enforced separately by token.
        'cookie'   => ['secure' => env('APP_ENV', 'production') !== 'local', 'httponly' => true, 'samesite' => 'Lax'],
    ],
    'limits' => [
        // Amounts are whole BDT. Admin panel overrides live in Firestore settings/app.
        'min_deposit'  => (int)env('MIN_DEPOSIT', 100),
        'max_deposit'  => (int)env('MAX_DEPOSIT', 25000),
        'min_withdraw' => (int)env('MIN_WITHDRAW', 100),   // TashPay doc: withdraw >= 100 taka
        'max_withdraw' => (int)env('MAX_WITHDRAW', 25000),
        'deposit_fee_percent'  => (float)env('DEPOSIT_FEE_PERCENT', 0),
        'withdraw_fee_percent' => (float)env('WITHDRAW_FEE_PERCENT', 0),
    ],
    'otp' => [
        'ttl'          => (int)env('OTP_TTL_SECONDS', 300),
        'resend_after' => (int)env('OTP_RESEND_SECONDS', 60),
        'max_attempts' => (int)env('OTP_MAX_ATTEMPTS', 5),
        'length'       => 6,
    ],
    'firebase' => [
        'project_id'  => env('FIREBASE_PROJECT_ID'),
        'web_api_key' => env('FIREBASE_WEB_API_KEY'),            // server-side only
        'credentials_b64' => env('FIREBASE_SERVICE_ACCOUNT_JSON_B64'),
    ],
    'tashpay' => [
        'base_url'   => rtrim((string)env('TASHPAY_BASE_URL', 'https://api.tashpay.org/api'), '/'),
        'account'    => env('TASHPAY_ACCOUNT'),
        'api_key'    => env('TASHPAY_API_KEY'),
        'deposit_notify_url'  => env('TASHPAY_DEPOSIT_NOTIFY_URL'),
        'withdraw_notify_url' => env('TASHPAY_WITHDRAW_NOTIFY_URL'),
        'enforce_ip' => (bool)env('TASHPAY_ENFORCE_CALLBACK_IP', false),
        'callback_ips' => array_filter(array_map('trim', explode(',', (string)env('TASHPAY_CALLBACK_IPS', '')))),
        'wallet_types' => ['bKash', 'Nagad'],   // exact spelling required by TashPay docs
        'timeout'    => 20,
    ],
    'turnstile' => [
        'site_key'   => env('TURNSTILE_SITE_KEY'),               // the only key allowed in frontend
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
        'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    ],
    'smtp' => [
        'host' => env('SMTP_HOST'), 'port' => (int)env('SMTP_PORT', 587),
        'user' => env('SMTP_USER'), 'pass' => env('SMTP_PASS'),
        'from_email' => env('SMTP_FROM_EMAIL'), 'from_name' => env('SMTP_FROM_NAME', 'AF MONEY SAFER'),
    ],
    'brevo' => [
        'api_key'    => env('BREVO_API_KEY'),
        'from_email' => env('BREVO_FROM_EMAIL', env('SMTP_FROM_EMAIL')),
        'from_name'  => env('BREVO_FROM_NAME', env('SMTP_FROM_NAME', 'AF MONEY SAFER')),
    ],
    'cron_secret' => env('CRON_SECRET'),
    'admin' => [
        'setup_token' => env('ADMIN_SETUP_TOKEN', ''),
    ],
];
