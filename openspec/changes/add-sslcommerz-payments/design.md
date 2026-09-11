## Context

See `proposal.md` — Why.

The pieces this builds on already exist:

- `app/Payments/Contracts/PaymentGateway.php` — `key()` / `label()` / `isConfigured()` / `charge()`.
- `app/Payments/PaymentResult.php` — an immutable result with a `redirect()` constructor that
  `OrderController::store()` already honours via `requiresRedirect()`.
- `app/Payments/PaymentGatewayManager.php` — gateways resolved by key; `available()` filters on
  `isConfigured()`, and `PlaceOrderRequest` validates `payment_method` against exactly that list.
- `payments` table — append-only attempt log (`gateway`, `gateway_reference`, `amount`, `currency`,
  `status`, `payload` json, `paid_at`).
- `OrderController::store()` — already places the order, reserves stock, notifies, then calls
  `charge()` and redirects away when a redirect is returned. **No change is needed there for the happy
  path**; only the `GatewayNotConfigured` catch block needs a sibling for gateway errors.

Constraints that shape the design:

- The app uses the **legacy Laravel skeleton** (`app/Http/Kernel.php`, `bootstrap/app.php` returning an
  `Application`) even on Laravel ^13. CSRF exemption therefore goes in
  `App\Http\Middleware\PreventRequestForgery::$except`, and middleware aliases live in `Kernel`
  (`checkrole` is already registered).
- PHP 8.3, `declare(strict_types=1)`, typed signatures, money as `decimal(10,2)`, FormRequests for
  validation, logic in action/service classes, real foreign keys, a feature test per feature —
  the conventions in `PLAN.md` — Conventions for New Code.
- `PaymentStatus` stays at four cases (pending/paid/failed/refunded) by decision; manual settlement
  lands on `Paid`.

SSLCommerz v4 surface used here:

| Purpose | Path (host varies by sandbox flag) |
| --- | --- |
| Create session | `POST /gwprocess/v4/api.php` → `{status: SUCCESS\|FAILED, GatewayPageURL, sessionkey}` |
| Validate a transaction | `GET /validator/api/validationserverAPI.php?val_id=…` → `{status: VALID\|VALIDATED\|INVALID_TRANSACTION, amount, currency, tran_id, bank_tran_id}` |
| Query by our own tran_id | `GET /validator/api/merchantTransIDvalidationAPI.php?tran_id=…` → `{element: [...]}` |

Hosts: sandbox `https://sandbox.sslcommerz.com`, live `https://securepay.sslcommerz.com`. **The stub's
doc comment currently names the live host as the sandbox host** — correct it while implementing.

## Goals / Non-Goals

**Goals:**

- One place decides whether a callback settles an order, shared by the success return and the IPN, so the
  two can never disagree.
- Every rejection path produces either a `payments_problem` row or a deliberate no-op; nothing fails
  silently.
- The gateway HTTP surface is injectable, so the whole flow is testable with `Http::fake()` and no
  network.

**Non-Goals:**

- bKash — `BkashGateway` stays a stub.
- Refunds (`PLAN.md` 3.5) and partial payments.
- Retrying a *customer-facing* payment from the order page. Recovery here is admin-side only.
- Recording customer cancellations or abandonment (explicit product decision).
- A queue/worker for callbacks — IPN handling is synchronous and must finish fast.

## Decisions

### 1. `payments_problem` is a separate table, not a status on `payments`

The `payments` table is an append-only attempt log keyed to an order. A problem row needs things that do
not fit it: a reason category, a free-text message, a resolution (who/when/how/reference/note), and —
critically — **a nullable `order_id`**, because a callback bearing an unknown `tran_id` has no order to
attach to and still must be recorded.

*Alternative considered:* add `failure_reason` + `resolved_at` to `payments`. Rejected — it would force
`payments.order_id` to become nullable, weakening the integrity of the settlement log for the sake of the
exceptional path.

The table name `payments_problem` is the user's (it breaks Laravel's plural convention), so the model
`App\Models\PaymentProblem` sets `protected $table = 'payments_problem';` explicitly.

Shape:

```
id
order_id            nullable, FK → orders, nullOnDelete
gateway             string, default 'sslcommerz'
gateway_reference   string, nullable, indexed        -- our tran_id
val_id              string, nullable                 -- gateway-side validation id, for re-check
reason              string, indexed                  -- declined | validation_mismatch | initiation_error
message             text                             -- human-readable, shown in the admin list
amount              decimal(10,2), nullable          -- what the gateway reported, may differ from order
currency            char(3), nullable
payload             json, nullable                   -- raw request/response
resolution          string, nullable                 -- gateway_recheck | manual
resolved_by         nullable FK → users, nullOnDelete
resolved_at         timestamp, nullable
resolution_reference string, nullable
resolution_note     text, nullable
timestamps
```

`reason` and `resolution` are backed by `ProblemReason` / `ProblemResolution` string enums cast on the
model, matching how the codebase already treats `OrderStatus` and `PaymentStatus`.

### 2. One settlement path: `SettlePaymentAction`

Four callers can settle an order (IPN, success return, admin re-check, admin manual). Each doing its own
`DB::transaction` with its own idempotency check invites divergence — exactly how double-settlement bugs
happen.

So: a single `App\Actions\SettlePaymentAction` is the only thing that writes a paid state. It takes the
order, the reference, the amount, and a settlement source (`gateway` | `manual`), then inside one
transaction:

1. `Order::lockForUpdate()` on the order row.
2. If `payment_status` is already `Paid` → return "already settled" without writing. This is the
   idempotency guarantee for duplicate IPNs *and* for two admins clicking at once (spec:
   "An order is never settled twice").
3. Insert a `payments` row (`status: paid`, `paid_at: now()`, `gateway: 'sslcommerz'` or `'manual'`).
4. Set `payment_status = Paid`; set `status = OrderStatus::Paid` when the current status allows the
   transition (`OrderStatus::canTransitionTo`) — an order already `shipped` must not be dragged back.

*Alternative considered:* a model observer on `Payment`. Rejected — implicit, and it makes the "already
paid" short-circuit hard to express.

### 3. Validation lives in `SslCommerzValidator`, separate from the controller

`App\Payments\SslCommerz\SslCommerzValidator::validate(string $tranId, ?string $valId, Order $order)`
returns a small result object: settle / reject-with-reason. It performs, in order:

1. Resolve the order from `tran_id` — unknown ⇒ `validation_mismatch`.
2. Call the gateway validation API (by `val_id`) or the transaction-query API (by `tran_id` when there is
   no `val_id`, i.e. the admin re-check path).
3. `status` must be `VALID` or `VALIDATED` — otherwise `declined` for an explicit failure,
   `validation_mismatch` for anything else.
4. `currency` must equal `$order->currency`.
5. `amount` must be `>=` the order grand total, compared to 2 decimal places using integer paisa
   (`(int) round($x * 100)`) — never float `==`. Gateways can report a slightly larger settled amount;
   a *smaller* one is a mismatch.

Both the IPN endpoint and the admin re-check call this same method, differing only in what they do with
the result. That is what makes the "re-check" button trustworthy rather than a second implementation.

### 4. IPN signature verification before anything else

The IPN POST carries `verify_sign` and `verify_key`. Verification recomputes the MD5 over the listed keys
plus `md5(store_password)` and compares with `hash_equals`. A bad signature ⇒ `validation_mismatch`
problem row and HTTP 400, **without** calling the validation API — otherwise the endpoint becomes an
unauthenticated way to make the app hammer SSLCommerz.

The signature is a first filter, not the authority: a valid signature still goes through the full
validation-API check above.

### 5. Transaction reference format and uniqueness

`tran_id` = `{order_number}-{6 random uppercase alphanumerics}`, e.g. `ORD-2026-000042-K3F9QZ`. Fits
SSLCommerz's 30-char limit, is unique per attempt (spec requirement), and stays human-traceable to its
order. The order is resolved from it by looking up `payments.gateway_reference`, **not** by string-parsing
the reference — the lookup must not trust attacker-controlled text.

### 6. Routes and middleware

```php
// public, no auth
Route::post('payment/sslcommerz/ipn', …)->name('payment.sslcommerz.ipn')
    ->middleware('throttle:60,1');
Route::match(['get','post'], 'payment/sslcommerz/success', …)->name('payment.sslcommerz.success');
Route::match(['get','post'], 'payment/sslcommerz/fail',    …)->name('payment.sslcommerz.fail');
Route::match(['get','post'], 'payment/sslcommerz/cancel',  …)->name('payment.sslcommerz.cancel');
```

`match(['get','post'])` because SSLCommerz POSTs the customer back to these URLs — a GET-only route would
405 the returning customer. All four go in `PreventRequestForgery::$except` as `payment/sslcommerz/*`:
they are cross-origin POSTs from the gateway and can carry no CSRF token. They are safe to exempt
precisely because none of them trusts its own request body — `success` re-validates server-side, and the
IPN additionally checks the signature.

Admin routes join the existing `admin.` prefix group, guarded by `auth` + `checkrole` in the controller
constructor, mirroring `Admin\OrderController`.

### 7. HTTP client wrapper

`App\Payments\SslCommerz\SslCommerzClient` wraps `Http::asForm()->timeout(30)->retry(2, 200)` around the
three endpoints and is injected into both the gateway and the validator. Tests use `Http::fake()`; no
separate interface or mock double is needed. Timeout and connection exceptions surface as
`SslCommerzRequestFailed`, which the callers turn into an `initiation_error` problem row.

### 8. Config additions

`config/payment.php` gains, under `gateways.sslcommerz`:

```php
'sandbox_url' => 'https://sandbox.sslcommerz.com',
'live_url'    => 'https://securepay.sslcommerz.com',
```

read through one `baseUrl()` helper driven by the existing `sandbox` flag, so no call site picks a host by
hand. Credentials stay in `.env` (`SSLCOMMERZ_STORE_ID`, `SSLCOMMERZ_STORE_PASSWORD`,
`SSLCOMMERZ_SANDBOX`, `PAYMENT_SSLCOMMERZ_ENABLED`) and are read only via `config()`.

### 9. Manual settlement keeps `PaymentStatus::Paid`

Per the user's decision, no fifth enum case. Origin is preserved twice over: the `payments` row carries
`gateway: 'manual'`, and the resolved `payments_problem` row carries `resolution` + `resolved_by`. The
admin order detail page labels a payment whose gateway is `manual` as "recorded manually" so the
distinction is visible without a query.

## Risks / Trade-offs

- **[IPN arrives before the redirect response is written]** The customer can be returned to the success
  URL while the IPN is mid-flight, or vice versa → both paths run the same idempotent
  `SettlePaymentAction` behind a row lock, so whichever lands second is a no-op.

- **[IPN never arrives]** (misconfigured URL, store not reachable from the internet) Orders sit `pending`
  with money taken → the admin re-check button works from the order's `tran_id` alone via the
  transaction-query API, so staff can settle without waiting on the gateway to retry. Worth a scheduled
  reconciliation job later; explicitly out of scope here.

- **[`payments_problem` becomes a noise dump]** Repeated declines from one customer bury real mismatches
  → the list defaults to unresolved and filters by reason; `validation_mismatch` is the category that
  warrants attention and is filterable on its own.

- **[Manual settlement is a fraud surface]** An admin can mark any order paid with no money received →
  a reference is mandatory, `resolved_by` and `resolved_at` are recorded, and the `payments` row is
  appended rather than overwritten, so the trail survives. Nothing here prevents an authorised admin from
  lying; it only makes it attributable.

- **[Reusing `paid` loses reconciliation granularity]** A finance report cannot distinguish gateway from
  manual money by `payment_status` alone; it must join `payments.gateway` → accepted trade-off, chosen
  deliberately to avoid migrating every status badge, filter, and transition table.

- **[Credentials in logs]** The raw payload stored in `payments_problem.payload` and logged on error can
  contain card metadata → strip `store_passwd`, `verify_sign`, and `verify_key` before persisting or
  logging; SSLCommerz never returns a PAN, but the store password does travel in the request array.

## Migration Plan

1. Ship the migration (`create_payments_problem_table`) — additive, no existing table touched, so it
   deploys ahead of the code safely.
2. Deploy the code with `PAYMENT_SSLCOMMERZ_ENABLED=false`. The gateway stays hidden at checkout because
   `available()` filters on `isConfigured()`; COD is unaffected.
3. Configure the sandbox store, set `SSLCOMMERZ_SANDBOX=true` and enable the flag in staging. Register the
   IPN URL in the SSLCommerz merchant panel. Run a live sandbox order end to end, plus one deliberately
   failed card to confirm a `payments_problem` row appears and resolves.
4. Flip to live credentials with `SSLCOMMERZ_SANDBOX=false`.

**Rollback:** set `PAYMENT_SSLCOMMERZ_ENABLED=false`. The gateway disappears from checkout instantly; the
callback routes stay reachable so in-flight payments still settle. Only drop the table if the feature is
abandoned outright — it holds the record of money that may still be owed.

## Open Questions

- Should an unresolved `payments_problem` row notify anyone (admin email, dashboard badge) rather than
  waiting to be found? Deferred: it adds a notification path without changing any requirement, table
  column, or task here.
- How long should raw `payload` be retained? A later retention policy can prune the column without
  touching this design.
