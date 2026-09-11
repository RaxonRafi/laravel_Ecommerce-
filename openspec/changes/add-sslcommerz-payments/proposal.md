## Why

The store can only take cash on delivery. `SslCommerzGateway::charge()` throws `GatewayNotConfigured`, so
every online order falls through to the "we will contact you to arrange payment" branch in
`OrderController::store()` — the customer never reaches a payment page and the shop carries the collection
risk. PLAN.md task 3.3 (payment flow) is the last blocker on Phase 3.

Online payments also fail for reasons the customer cannot fix: the card is declined, the gateway times out
before a session is created, or an IPN arrives whose amount does not match the order. Today those failures
are invisible — the order sits `pending` with no record of what went wrong and no way for staff to recover
the money. This change both completes the SSLCommerz integration and gives failed attempts a home that an
admin can work through.

## What Changes

- Implement `SslCommerzGateway::charge()` against the SSLCommerz v4 hosted-checkout API: create a session,
  persist the transaction id on the pending `payments` row, and redirect the customer to `GatewayPageURL`.
- Add public callback endpoints for `success`, `fail`, `cancel` and `ipn`. The **IPN is the only source of
  truth** for settlement: every callback is validated server-side against SSLCommerz's validation API and
  the order's own amount and currency before an order is marked paid. The browser redirect only decides
  which page the customer lands on.
- Add a `payments_problem` table recording each failed attempt: the order, the gateway reference, the
  reason category, the amount the gateway reported, the raw payload, and the resolution state. Rows are
  written when the gateway declines a transaction, when server-side validation of a callback fails
  (amount/currency/signature/unknown order), and when the session request errors or times out. Customer
  cancellations are *not* problems and are not recorded here.
- Add an admin "Problem Payments" screen listing unresolved rows, with two actions per row:
  - **Re-check with gateway** — re-queries SSLCommerz by transaction id; if the gateway now reports the
    transaction valid for the right amount, the order settles automatically.
  - **Record manual payment** — for money that arrived out of band, capturing a reference and a note.
  Both write a new `payments` row and move the order's `payment_status` to the existing
  `PaymentStatus::Paid`; the manual origin is preserved on the `payments_problem` row (`resolved_by`,
  `resolution`) and on the payment's `gateway` column, so reconciliation can still tell the two apart.
- Add `SSLCOMMERZ_*` entries to the environment documentation and make the gateway's sandbox/live host
  selection explicit. (The stub's current doc comment names the *live* host as the sandbox host.)

No breaking changes: cash on delivery, existing orders, and the `payments` audit trail are untouched, and
`PaymentStatus` keeps its four cases.

## Capabilities

### New Capabilities
- `payments/sslcommerz-checkout`: Taking payment for an order through the SSLCommerz hosted page —
  session creation, redirect, the success/fail/cancel/IPN callbacks, and the server-side validation that
  decides whether an order is settled.
- `payments/problem-payments`: Recording payment attempts that failed, and the admin workflow that
  resolves them — re-checking with the gateway or recording an out-of-band payment.

### Modified Capabilities
<!-- None: the project has no existing specs (`openspec list --specs` returns none), so nothing
     previously specified changes. -->

## Impact

**Code**
- `app/Payments/Gateways/SslCommerzGateway.php` — implement `charge()`, add session creation and
  transaction re-query.
- `app/Payments/` — new client/validator collaborators for the SSLCommerz HTTP API.
- `app/Http/Controllers/PaymentCallbackController.php` — new; success/fail/cancel/IPN endpoints.
- `app/Http/Controllers/Admin/ProblemPaymentController.php` — new; index + two resolution actions.
- `app/Models/PaymentProblem.php`, new migration `create_payments_problem_table`.
- `app/Models/Order.php` — `paymentProblems()` relation.
- `routes/web.php` — public callback routes (CSRF-exempt for the IPN) and admin routes.
- `resources/views/admin/` — problem payments index/detail views; a link from the admin order detail page.
- `config/payment.php` — SSLCommerz endpoint hosts.

**External**
- Outbound HTTPS to `sandbox.sslcommerz.com` / `securepay.sslcommerz.com`.
- Inbound: the IPN URL must be publicly reachable and is unauthenticated, so it is rate-limited and
  verified by signature rather than by session.

**Data**
- New `payments_problem` table. No changes to `orders`, `payments`, or any enum.
