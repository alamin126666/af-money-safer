# AF MONEY SAFER

PHP 8.2 + Firebase Firestore/Auth (Admin SDK) + TashPay (bKash / Nagad), deployed on Render (Docker).
Bangla + English, light/dark, custom SVG icons, custom OTP number pad, admin panel, cron reconciliation.

## What is in the box
| Area | Files |
|---|---|
| Pages | `public/*.php` (index = 5 s loader -> login, register, verify, forgot-password, reset-password, dashboard, deposit, withdraw, history, profile, notifications, admin) |
| API | `public/api/{auth,deposit,withdraw,history,dashboard,profile,notifications,admin,cron}/*.php` - JSON `{success, code, message, data, timestamp}` |
| Money logic | `src/Services/WalletService.php` (only code that changes balances: Firestore transactions + immutable ledger), `PaymentService.php`, `TashPay.php`, `ReconcileService.php` |
| Security core | `src/Core/*` (sessions in Firestore, CSRF, rate limit, validator, headers), `src/Services/AuthService.php`, `OtpService.php`, `Turnstile.php` |
| Front end | `public/assets/css/app.css`, `public/assets/js/{core,otp,history,i18n}.js`, `pages/*.js`, `templates/` |
| Deploy | `Dockerfile`, `docker/apache.conf`, `render.yaml`, `firestore.rules`, `firestore.indexes.json`, `.env.example` |
| Cron | `scripts/reconcile.php` (every 5 min), `scripts/make-admin.php` |
| Tests | `scripts/selftest.php`, `scripts/selftest_wallet.php` |
| Docs | `docs/DEPLOY.md` (start here), `docs/FIRESTORE_SCHEMA.md`, `docs/AUTH_FLOW.md`, `docs/TASHPAY_NOTES.md`, `docs/SECURITY.md` |

## How money flows
**Deposit:** amount validated -> pending `transactions` + `payment_orders` -> TashPay `/repay/link` -> user pays on TashPay page ->
balance is credited **only** by a signature-verified callback (`/api/deposit/notify.php`) or by a server-side `/repay/result` query
(status page poll + cron). The return URL alone never credits anything. Credit uses a deterministic ledger id, so repeated callbacks cannot double-credit.

**Withdraw:** form -> e-mail OTP -> balance **held** (amount + fee) in one transaction -> TashPay `/withdraw` ->
callback / `/withdraw/result` decides: success releases the hold, failure refunds it. If TashPay cannot be reached the request stays
`pending` and the cron job resolves it; it is never shown as successful and never refunded blindly.

## Quick start
1. Read `docs/DEPLOY.md` and fill the environment variables (`.env.example`).
2. Push to a **private** GitHub repo, create the Render Blueprint from `render.yaml`.
3. `php scripts/make-admin.php you@example.com super` in the Render shell.

## Run the offline tests
`composer install && php scripts/selftest.php && php scripts/selftest_wallet.php`
