# TashPay integration notes (extracted from tashpay-API接入文档.docx)

Only what the document states. Nothing below is assumed.

## Common
- Base URL `https://api.tashpay.org/api`; all requests/responses JSON; method POST.
- Merchant account goes in request header `account` (or body field `header_account`).
- `timestamp` in ms; must be within 120 min of platform time, else code 5005.
- Response envelope `{code, msg, data}`; `code 0` = success.
- Optional IP whitelist on the merchant account (code 5009 if blocked). Empty or `0.0.0.0` = unrestricted.
- Callbacks come from `43.98.164.189` and `43.98.196.171`.
- Callback retry: up to 5 times unless body is exactly `SUCCESS`.

## Signature
Sort params by key ascending -> `k=v&k=v` (skip `sign`, null/blank/whitespace values, array values) -> append `&key=API_KEY`
-> `md5()` lowercase -> send as `sign`. Applies to requests and to callbacks.

## Endpoints used
| Purpose | Path | Body (besides timestamp, sign) | Response data |
|---|---|---|---|
| Deposit (hosted cashier) | `/repay/link` | orderId(<=50), amount, customerName, notifyUrl; optional customerMobile, walletType, returnUrl | transactionId, url |
| Deposit (account mode) | `/repay/account` | orderId, walletType, amount, notifyUrl; optional paymentWalletNo, customerName, customerMobile | transactionId, receiveWalletNo, amount, walletType, isCashOut |
| Deposit result | `/repay/result` | merchantRef | orderId, transactionId, status (0 init, 1 success), amount, actualAmount, transactionTime, fee |
| Deposit manual confirm | `/repay/reconfirm` | orderId (platform order id), flowId | status 1/2/3, amount, actualAmount, desc |
| Withdraw | `/withdraw` | orderId, amount (integer, >=100), walletType (`Nagad`/`bKash`), receiveUsername, receiveWalletNo, notifyUrl | transactionId, status (0 = queued) |
| Withdraw result | `/withdraw/result` | id (platform) or merchantRef | orderId, transactionId, flowId, status (0 queue, 1 success, 2 failed), errorMsg, amount, fee |
| Merchant balance | `/merchant/balance` | - | account, balance |

Not used in the base build: `/repay/api/info`, `/repay/submit/transaction` (API-mode payments).

## Callbacks
- Deposit notify body: account, orderId, transactionId, status (1 = success), errorMsg, amount (actual paid, 2 decimals), fee, transactionTime, sign.
- Withdraw notify body: account, orderId, transactionId, flowId, status (1 success / 2 failed), errorMsg, amount, fee, sign.
- Respond with plain body `SUCCESS` after verifying + recording; do heavy work afterwards.

## Decisions implemented (change in code/config if you disagree)
1. **Deposit credit** = the verified *actual* paid amount (callback `amount` / result `actualAmount`). If it differs from the requested amount the deposit is still credited and an `audit_logs` entry `deposit.amount_mismatch` is written.
2. **Fees**: deposit fee % (default 0) is deducted from the credited amount; withdraw fee % (default 0) is charged **on top** of the requested payout. Both editable in Admin -> Settings.
3. **Withdraw amount** is a whole number, minimum 100 (hard floor in code, per the document).
4. `/repay/reconfirm` and `/repay/api/info` / `/repay/submit/transaction` are **not used** (API-mode payments and manual confirmation are out of scope; the doc's reconfirm example and field table disagree).
5. Withdraw rejection codes that trigger an immediate refund: 5001-5006, 5009-5012, 5014-5016, 5023. Code 5007 (duplicate) and any unclear answer keep the hold and are resolved by `/withdraw/result`.
6. A payout reported successful **after** we refunded is never auto-debited again: the transaction is flagged `needsReview` (shown in Admin overview) and `withdraw.paid_after_refund` is logged.
7. **IP whitelist**: see DEPLOY.md section 4.
