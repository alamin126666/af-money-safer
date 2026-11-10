# Deployment guide (about 45 minutes)

## 1. Firebase
1. Create a project -> **Authentication -> Sign-in method -> Email/Password: enable** (leave "email link" off).
2. **Firestore Database** -> create in production mode (pick a region close to Bangladesh, e.g. `asia-south1`).
3. Deploy rules + indexes: `npm i -g firebase-tools && firebase login && firebase deploy --only firestore:rules,firestore:indexes --project YOUR_ID`
   (rules deny ALL browser access; the server uses the Admin SDK). Wait until the indexes show "Enabled".
4. **Project settings -> Service accounts -> Generate new private key.** Open the file and paste its **whole content** (or its base64) into `FIREBASE_SERVICE_ACCOUNT_JSON_B64`. Never commit the file.
5. **Project settings -> General -> Web API key** -> `FIREBASE_WEB_API_KEY` (used only server-side for password checks).

## 2. Cloudflare Turnstile
Dashboard -> Turnstile -> add widget for your Render domain -> `TURNSTILE_SITE_KEY` (public) and `TURNSTILE_SECRET_KEY` (secret).

## 3. SMTP (OTP e-mails)
Any SMTP provider (Brevo, Mailgun, SES, Gmail app password). Fill `SMTP_*`. `SMTP_FROM_EMAIL` must be a verified sender. Without working SMTP nobody can register or log in.

## 4. TashPay
Give TashPay these callback URLs (they are also in env):
- Deposit: `https://YOUR-DOMAIN/api/deposit/notify.php`
- Withdraw: `https://YOUR-DOMAIN/api/withdraw/notify.php`

Set `TASHPAY_ACCOUNT` and `TASHPAY_API_KEY`. If your TashPay merchant has an **IP whitelist**, Render's outbound IPs are shared per region and
are listed in the Render dashboard (service -> Connect -> Outbound); ask TashPay to whitelist them or leave it open.
TashPay calls back from `43.98.164.189` / `43.98.196.171`; set `TASHPAY_ENFORCE_CALLBACK_IP=true` only after you confirmed in the logs
(`notify.unexpected_ip` events) that the IP header on Render shows the real source.

## 5. Render
1. Private GitHub repo -> Render -> **New -> Blueprint** -> pick the repo (`render.yaml` creates the web service + the 5-minute cron job).
2. Fill the `af-secrets` environment group. `APP_URL` = the final https URL without trailing slash.
3. First build takes 8-12 minutes (gRPC extension is compiled).
4. Use a **paid** plan for the web service; free instances sleep and callbacks/cron would be delayed.

### 5b. Scheduler without a Render Cron Job (cron-job.org)
`CRON_SECRET` is optional (leave it empty for an open endpoint, or set e.g. `openssl rand -hex 24` and send it as header). In cron-job.org create **two** jobs:
1. `https://YOUR-DOMAIN/api/cron/run.php`, every 5 min, method GET (header `X-Cron-Secret: <your secret>` only if you set one) -> reconciles pending payments + cleanup.
2. `https://YOUR-DOMAIN/api/cron/health.php`, every 1 min -> only keeps a free instance awake / uptime check (no secret needed).
You can delete the `type: cron` block in `render.yaml`. Free Render instances may still restart and need ~30-60 s to wake; callbacks sent
meanwhile are retried by TashPay and picked up by the reconcile job. For real money use a paid plan.

## 6. First admin
Render -> web service -> Shell: `php scripts/make-admin.php you@example.com super`
(register and verify that e-mail in the site first). Roles: `viewer` (read), `operator` (+users, settings), `super` (+balance adjustments).

## 7. Go-live checklist (do these in order, with small amounts)
- [ ] Register -> OTP e-mail arrives (Bangla and English) -> dashboard shows 0.00.
- [ ] Wrong password 5x locks login for 15 min; wrong OTP 5x locks the code.
- [ ] Forgot password -> new password works; old sessions are signed out.
- [ ] Deposit minimum amount via bKash: balance rises **only after** callback; replay the callback (TashPay retries) -> no second credit.
- [ ] Close the cashier without paying -> transaction stays pending, balance unchanged.
- [ ] Withdraw minimum amount: OTP -> hold -> callback success; then force a failure case -> balance returned.
- [ ] Stop the web service for 10 min during a pending payment -> cron resolves it after restart.
- [ ] `/api/cron/health.php` returns ok; Render logs show `cron.reconcile` every 5 minutes.
- [ ] Admin: settings change appears in Audit; balance adjustment demands password + reason.

## 8. Things only you can decide / obtain
TashPay production credentials and written confirmation of the doc inconsistencies (see TASHPAY_NOTES.md), SMTP sender domain (SPF/DKIM),
a custom domain, and **legal/regulatory clearance** for holding user balances in Bangladesh before accepting real money.
