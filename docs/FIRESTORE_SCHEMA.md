# Firestore Schema (AF MONEY SAFER)

All access is server-side (Admin SDK). `firestore.rules` denies every client read/write.
**Money is stored as integer poisha (1 BDT = 100)** to avoid float errors. Times are integer milliseconds (`createdAtMs`, `updatedAtMs`, ...) so ordering/cursors are exact; `users.createdAt` and `audit_logs.createdAt` are Firestore server timestamps. Other expiry fields (`expiresAt`) are epoch seconds.

| Collection | Doc ID | Key fields |
|---|---|---|
| `users` | Firebase Auth UID | fullName, email, emailLower, status (`active`/`disabled`), emailVerified, avatar {type:`preset`/`upload`, key}, lang, theme, createdAt, lastLoginAt |
| `wallets` | UID | balance, held, totalDeposit, totalWithdraw, version, updatedAt |
| `transactions` | random 20-char | userId, type (`deposit`/`withdraw`), method (`bKash`/`Nagad`), amount, fee, status (`pending`/`processing`/`successful`/`failed`/`cancelled`), merchantOrderId, platformTxId, flowId, walletNo (masked), holderName, errorMsg, createdAt, updatedAt |
| `ledger` | deterministic (see below) | userId, txId, kind, amount (signed, effect on available balance), heldDelta, balanceAfter, reason, actor, createdAtMs — **create-only, never updated/deleted** |
| `payment_orders` | merchantOrderId (<=50 chars) | txId, userId, direction, requestedAmount, tashpayRequestHash, lastQueryAt |
| `webhook_events` | sha256(dir+orderId+transactionId+status+amount) | direction, receivedAt, processedAt, result |
| `otp_requests` | random | userId/email, purpose, codeHmac, attempts, maxAttempts, expiresAt, consumedAt, lastSentAt, ipHash |
| `sessions` | session id hash | userId, data, expiresAt (Render disk is ephemeral, so PHP sessions live here) |
| `rate_limits` | key hash | count, windowStart, blockedUntil |
| `avatars` | UID | data (256x256 JPEG blob), mime, key |
| `notifications` | random | userId, titleKey, bodyKey, params, read, createdAt |
| `audit_logs` | random | actor, action, target, before, after, ipHash, createdAt |
| `settings` | `app` | limits, fees, maintenance flag, updatedBy |
| `admin_users` | UID | role (`viewer`/`operator`/`super`), permissions[], active |

## Idempotent ledger IDs
Balance changes happen inside one Firestore transaction: read wallet + tx + (create-only ledger doc) -> write wallet + tx.
Ledger doc ID makes a second credit impossible (`create()` fails if the doc exists):

- `dep_credit_{txId}` - deposit success
- `wd_hold_{txId}` - balance -> held
- `wd_settle_{txId}` - held released on success
- `wd_refund_{txId}` - held -> balance on failure
- `adj_{uuid}` - admin adjustment (requires reason + permission + audit_log)

## Withdraw state machine
`pending` (hold created) -> `processing` (TashPay accepted, status 0) -> `successful` (callback/query status 1) | `failed` (status 2 -> refund).
A request that never reached TashPay (network error before response) stays `pending` and is resolved by the cron job through
`/withdraw/result` - it is never marked successful or refunded blindly.
