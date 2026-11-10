# Security summary (what is implemented, where)

| Requirement | Implementation |
|---|---|
| Firebase Auth / passwords never plain | Passwords only exist in Firebase Auth (hashed by Google). Login verifies them server-side via Identity Toolkit. |
| Firestore rules | `firestore.rules`: deny all client access. Only the PHP backend (Admin SDK) reads/writes. |
| Session security + regeneration | `Session.php`: `__Host-` cookie, Secure, HttpOnly, SameSite=Lax, 60 min idle / 12 h absolute, stored in Firestore, id regenerated after OTP success / reset. |
| CSRF | Per-session token, header `X-CSRF-Token` on every state-changing call (`Csrf.php`). |
| XSS | Front end writes dynamic text with `textContent` or `AF.esc`; CSP allows scripts only from self + Turnstile; no inline JS. |
| Rate limiting / brute force | `RateLimit.php` (Firestore counters, works across instances): login, OTP, register, forgot, deposit, withdraw, photo, password checks. |
| OTP | `OtpService.php`: random_int, HMAC-stored, expiry, attempts, atomic single use, resend cooldown + hourly cap. |
| Webhook verification | `TashPay::verifyCallback` (MD5 sign per TashPay doc + merchant account), event de-duplication, idempotent ledger ids, `SUCCESS` only after durable processing. |
| Duplicate transaction / double spend | Firestore transactions + deterministic ledger ids (`dep_credit_*`, `wd_hold_*`, `wd_settle_*`, `wd_refund_*`). Tested in `selftest_wallet.php`. |
| Server-side balance validation | Balance, limits, fees are computed on the server; the browser only displays them. |
| Audit log | `audit_logs` (login, profile changes, admin actions, mismatches). |
| Admin control | `admin_users` + roles; balance adjustment needs `balance` permission, reason, re-entered password, ledger entry and audit log. |
| Uploads | 2 MB, MIME sniffing, `getimagesize`, GD re-encode to 256x256 JPEG, random key, served only to the owner. |
| Errors | `display_errors=Off`; users only see localized toast messages; details go to Render logs. |
| Secrets | Only env vars; `.gitignore` blocks `.env` and service-account files. |

## Known limits (be aware)
- MD5 is TashPay's protocol, not our security model; authenticity relies on keeping `TASHPAY_API_KEY` secret.
- Registration reveals whether an e-mail exists (spec requires the toast); it is rate limited. Forgot-password does not reveal it.
- Admin overview scans up to 5,000 users; fine for a start, add counters before large scale.
- Rate limiting by IP depends on the `CLIENT_IP_HEADER` being the real client IP on Render - verify in logs.
