## 1. Configuration and gateway plumbing

- [x] 1.1 Add `sandbox_url` / `live_url` to `config/payment.php` under `gateways.sslcommerz` and document
      `SSLCOMMERZ_STORE_ID`, `SSLCOMMERZ_STORE_PASSWORD`, `SSLCOMMERZ_SANDBOX`,
      `PAYMENT_SSLCOMMERZ_ENABLED` in `.env.example`. Verify with
      `php artisan tinker --execute="dump(config('payment.gateways.sslcommerz'));"` showing both hosts.
- [x] 1.2 Create `App\Payments\SslCommerz\SslCommerzClient` wrapping `Http::asForm()->timeout(30)->retry(2, 200)`
      with `createSession()`, `validateByValId()`, `queryByTransactionId()`, and a private `baseUrl()` that
      picks sandbox vs live from the `sandbox` config flag. Verify with a unit test using `Http::fake()`
      that asserts the sandbox host is used when the flag is on and the live host when it is off
      (spec: "Sandbox and live gateway hosts are selected explicitly").
- [x] 1.3 Add `App\Payments\Exceptions\SslCommerzRequestFailed` for timeouts, connection errors, and
      non-SUCCESS session responses. Verify a unit test asserts the client throws it when `Http::fake()`
      returns a connection exception or a `{"status":"FAILED"}` body.

## 2. Problem payments data model

- [x] 2.1 Create `App\Enums\ProblemReason` (`declined`, `validation_mismatch`, `initiation_error`) and
      `App\Enums\ProblemResolution` (`gateway_recheck`, `manual`), each with `label()` and `badge()` to
      match `PaymentStatus`. Verify a unit test round-trips every case through `from()`.
- [x] 2.2 Create migration `create_payments_problem_table` with the columns listed in design.md — decision 1
      (nullable `order_id` with `nullOnDelete`, nullable `resolved_by` FK to users, indexed
      `gateway_reference` and `reason`, json `payload`, `decimal(10,2)` amount). Verify
      `php artisan migrate` then `php artisan migrate:rollback` runs clean both ways.
- [x] 2.3 Create `App\Models\PaymentProblem` with `protected $table = 'payments_problem'`, enum/array/datetime
      casts, `order()` and `resolvedBy()` relations, an `isResolved()` accessor, and `scopeUnresolved()`.
      Add `paymentProblems()` to `App\Models\Order`. Verify a test creates a row with a null `order_id`
      and reads it back unresolved (spec: "Problem payments carry a resolution state").
- [x] 2.4 Create `App\Actions\RecordPaymentProblemAction` taking order (nullable), reason, message,
      reference, amount, currency and payload; it strips `store_passwd`, `verify_sign` and `verify_key`
      from the payload before persisting (design.md — Risks). Verify a test asserts those keys are absent
      from the stored payload.

## 3. Settlement action

- [x] 3.1 Create `App\Actions\SettlePaymentAction` per design.md — decision 2: lock the order row, return
      "already settled" when `payment_status` is already `Paid`, otherwise append a `payments` row
      (`status: paid`, `paid_at: now()`, `gateway: 'sslcommerz'|'manual'`) and set `payment_status = Paid`
      plus `status = OrderStatus::Paid` only when `canTransitionTo()` allows it. Verify a feature test
      settles a pending order and asserts both statuses and the new payment row.
- [x] 3.2 Verify idempotency with a test that calls the action twice for the same order and asserts exactly
      one `paid` payment row survives with its original `paid_at` (spec: "An order is never settled twice",
      "Notifications are safe to receive more than once").
- [x] 3.3 Verify a test that settles an order already in `OrderStatus::Shipped` leaves `status` at `shipped`
      while still setting `payment_status` to `paid` — no illegal backwards transition.

## 4. SSLCommerz charge flow

- [x] 4.1 Implement transaction-reference generation (`{order_number}-{6 random uppercase alphanumerics}`)
      and persist it on the order's pending `payments.gateway_reference` row. Verify a test placing two
      attempts for one order asserts two distinct references, each ≤ 30 characters
      (spec: "Transaction reference is unique per attempt").
- [x] 4.2 Implement `SslCommerzGateway::charge()`: build the session request from the stored order only
      (grand total, currency, customer and shipping fields, the four callback URLs by route name), call
      `SslCommerzClient::createSession()`, and return `PaymentResult::redirect($gatewayPageUrl, $tranId, …)`.
      Correct the class doc comment that names the live host as the sandbox host. Verify a feature test
      with `Http::fake()` asserts the customer is redirected to the faked `GatewayPageURL` and the order is
      `pending`/`pending` with stock reserved (spec: "Customer is redirected to the hosted payment page").
- [x] 4.3 Verify a test that posts an extra `amount` field with the order placement asserts the session
      request body carries the order's own grand total, not the submitted value
      (spec: "Amount and currency are derived from the order").
- [x] 4.4 Handle session failure in `OrderController::store()` by catching `SslCommerzRequestFailed`
      alongside the existing `GatewayNotConfigured`: record an `initiation_error` problem payment, keep
      the order pending, and redirect to the order page telling the customer payment could not be started.
      Verify a test faking a timeout asserts the order survives, one `initiation_error` row exists, and the
      customer is not shown an error page (spec: "Session request times out").

## 5. Callback validation

- [x] 5.1 Create `App\Payments\SslCommerz\SslCommerzValidator` implementing the ordered checks in
      design.md — decision 3: resolve the order via `payments.gateway_reference` (never by parsing the
      reference), call the validation or query API, require status `VALID`/`VALIDATED`, require matching
      currency, and compare amounts as integer paisa requiring `>=` the grand total. Return a result
      carrying either settle-approval or a `ProblemReason` plus message.
- [x] 5.2 Verify validator unit tests with `Http::fake()` cover each rejection branch and the success
      branch: valid transaction, amount short by 1.00, mismatched currency, `INVALID_TRANSACTION`,
      and unknown `tran_id` (spec: "Every callback is validated against the gateway before it is trusted").
- [x] 5.3 Implement IPN signature verification (MD5 over the `verify_key` fields plus `md5(store_password)`,
      compared with `hash_equals`) as a guard that runs *before* any outbound API call. Verify a test
      asserts a request with a bad `verify_sign` returns HTTP 400, records a `validation_mismatch` row,
      and makes no HTTP call to the gateway (`Http::assertNothingSent()`).

## 6. Callback endpoints

- [x] 6.1 Create `App\Http\Controllers\PaymentCallbackController` with `ipn`, `success`, `fail` and `cancel`
      actions, and register the four routes in `routes/web.php` using `match(['get','post'])` for the three
      browser returns, `throttle:60,1` on the IPN, plus `payment/sslcommerz/*` in
      `PreventRequestForgery::$except` (design.md — decision 6). Verify a test posts to the IPN with no
      session and no CSRF token and is not rejected
      (spec: "The notification endpoint is publicly reachable and unauthenticated").
- [x] 6.2 Implement `ipn`: verify signature, validate, then either call `SettlePaymentAction` or record a
      problem payment; respond 200 on settle and on a duplicate already-paid notification, 400 on a
      rejected one. Verify tests cover settle, duplicate-is-200-with-no-new-row, and rejected-is-400
      (spec: "Notification settles the order", "Duplicate notification").
- [x] 6.3 Implement `success`: re-validate server-side and settle only if validation passes; when it has not
      settled, show the order with a "payment is being confirmed" message rather than marking it paid.
      Verify tests cover the forged-return case (direct hit with an unconfirmed reference leaves the order
      pending with no successful payment row) and the not-yet-confirmed case
      (spec: "Settlement is decided by the gateway notification, not the browser").
- [x] 6.4 Implement `fail`: record a `declined` problem payment, leave the order pending, and show the
      customer that payment did not complete. Verify a test asserts one `declined` row and an unchanged
      order (spec: "A failed payment leaves the order unpaid and recorded").
- [x] 6.5 Implement `cancel`: return the customer to their order with an explanation and record nothing.
      Verify a test asserts `payments_problem` is empty afterwards and the order is still pending
      (spec: "A cancelled payment leaves the order recoverable").
- [x] 6.6 Verify a test asserts a callback whose `tran_id` matches no order is rejected and produces a
      `validation_mismatch` row with a null `order_id` (spec: "Callback names an unknown order").
- [x] 6.7 Verify the IPN rate limit with a test that exceeds `throttle:60,1` from one source and asserts
      HTTP 429 (spec: "Flooded endpoint").

## 7. Admin problem payments

- [x] 7.1 Create `App\Http\Controllers\Admin\ProblemPaymentController` (constructor `auth` + `checkrole`,
      mirroring `Admin\OrderController`) with `index` and `show`; register them under the existing `admin.`
      route group. Verify tests assert a customer gets 403 and a guest is redirected to login for both the
      list and the resolution endpoints (spec: "Only administrators reach the problem payments area").
- [x] 7.2 Implement `index`: newest first, defaulting to unresolved, filterable by resolution state and by
      reason, showing order number, reason, amount, time and state. Verify a test asserts a resolved row is
      absent by default and that filtering by `declined` excludes other reasons
      (spec: "Administrators can review problem payments").
- [x] 7.3 Build `resources/views/admin/problem-payments/index.blade.php` and `show.blade.php` following the
      existing `admin/orders` views, with the raw payload and a link to the order on the detail page.
      Verify the pages render for an admin with both an order-linked and an orderless row.
- [x] 7.4 Add an unresolved problem-payments indicator and link to `admin/orders/show.blade.php`, and label
      any payment whose gateway is `manual` as recorded manually. Verify a test asserts the order page shows
      the indicator when an unresolved row exists (spec: "Order detail links to its problems",
      "Manual settlement is distinguishable from gateway settlement").

## 8. Admin resolution actions

- [x] 8.1 Implement the `recheck` action: re-query SSLCommerz through `SslCommerzValidator` using the stored
      reference, settle via `SettlePaymentAction` and mark the row resolved with `gateway_recheck` when the
      gateway confirms; otherwise change nothing and report the outcome. Hide the action for rows with no
      `gateway_reference`. Verify tests cover confirmed, still-failing, confirmed-with-a-short-amount,
      gateway-unreachable, and the no-reference case (spec: "Administrators can re-check a problem payment
      with the gateway").
- [x] 8.2 Create `App\Http\Requests\ResolveProblemPaymentRequest` requiring `reference` (string, max 255) and
      allowing an optional `note`. Verify a test asserts a submission without a reference fails validation
      and leaves both the order and the problem row unchanged (spec: "Reference omitted").
- [x] 8.3 Implement the `resolveManually` action: settle the order for its full grand total with source
      `manual` via `SettlePaymentAction`, then store `resolution`, `resolved_by`, `resolved_at`,
      `resolution_reference` and `resolution_note` on the row. Verify a test asserts the order becomes
      `paid`/`paid`, the payment row has `gateway: 'manual'` and the supplied reference, and the problem row
      records the acting admin (spec: "Administrators can record a payment received out of band").
- [x] 8.4 Guard both actions against double resolution: refuse when the order is already paid or the row is
      already resolved, preserving the existing resolution and reporting why. Verify tests cover both
      refusals (spec: "Order already paid", "Problem already resolved").
- [x] 8.5 Verify concurrency with a test that runs two resolutions against the same problem payment inside
      the locking path and asserts exactly one takes effect with exactly one successful payment row
      (spec: "Two administrators resolve at once").

## 9. Integration and close-out

- [x] 9.1 Verify the gateway availability rules end to end: with credentials set, SSLCommerz appears on the
      checkout page; with a blank store password it is absent and a crafted `payment_method=sslcommerz`
      submission fails validation (spec: "SSLCommerz is offered only when fully configured").
- [x] 9.2 Run the full suite with `php artisan test` and confirm it passes with no regressions in
      `OrderPlacementTest`.
- [x] 9.3 Update `PLAN.md`: tick 3.1 (gateway chosen — SSLCommerz) and 3.3 (payment flow), and note that
      failed-payment recovery is handled by the problem payments screen.
