# Authentication flow (step 2)

All endpoints: JSON in/out, `{success, code, message, data, timestamp}`. POST needs header `X-CSRF-Token`
(get it from `GET /api/auth/csrf.php`, which also returns the Turnstile *site* key, language and login state).

| Endpoint | Input | On success |
|---|---|---|
| `GET  /api/auth/csrf.php` | - | csrfToken, turnstileSiteKey, lang, authenticated |
| `POST /api/auth/register.php` | fullName, email, password, confirmPassword, turnstileToken | OTP mailed (`register`), `next: verify` |
| `POST /api/auth/login.php` | email, password, turnstileToken | OTP mailed (`login`, or `register` if e-mail not yet verified) |
| `POST /api/auth/verify-otp.php` | code | session created -> `next: dashboard` (reset flow -> `next: reset-password`) |
| `POST /api/auth/resend-otp.php` | - | new code (cooldown 60 s, max 5/hour per e-mail) |
| `POST /api/auth/forgot-password.php` | email | same response whether the e-mail exists or not |
| `POST /api/auth/reset-password.php` | newPassword, confirmPassword | password changed, all sessions revoked, security e-mail sent |
| `POST /api/auth/logout.php` | - | session destroyed |

## Security behaviour
- Passwords live only in Firebase Auth (hashed by Firebase). Login checks them via Firebase's REST `signInWithPassword` using the Web API key, server-side only.
- OTP: `random_int` 6 digits, stored as HMAC-SHA256, 5 min TTL, 5 attempts, consumed atomically in a Firestore transaction, new code invalidates the old one.
- Session id regenerated after OTP success and password reset; cookie `__Host-` prefixed, Secure, HttpOnly, SameSite=Lax; 60 min idle / 12 h absolute; stored in Firestore.
- Wrong-password lockout: 5 per e-mail and 20 per IP per 15 min. Other limits: login 40/15 min per IP, register 10/h, forgot 3/h per e-mail, OTP verify 30/15 min per IP.
- Turnstile is verified server-side and fails closed (no secret or no answer from Cloudflare = request refused).
- Reset flow never reveals whether an e-mail is registered (a fake pending state is used for unknown e-mails).
- Registration does reveal "Email already exists" (your spec asks for this toast); it is rate limited per IP.

## Test locally
`composer install && php scripts/selftest.php` (offline logic tests: validation, language parity, OTP MAC, e-mail templates).
Turnstile has official dummy keys for development: site `1x00000000000000000000AA`, secret `1x0000000000000000000000000000000AA`.
