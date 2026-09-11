# Implementation Plan

Working document for completing the Goldfish eCommerce application. The [README](README.md)
describes *what* the project is; this file describes *what to build, in what order, and how to
know it's done*.

**Status legend:** `[ ]` not started · `[~]` in progress · `[x]` done

Every task lists the files it touches and an acceptance check. Tasks are ordered so that each
phase unblocks the next — **do not start Phase 2 before Phase 1**, because the order flow
inherits the cart's security bugs.

---

## Conventions for New Code

Adopted from the `laravel-specialist` skill and the gaps found in the audit. New code follows
these even though existing code doesn't; refactoring old code is Phase 5.

- `declare(strict_types=1);` at the top of new PHP files
- Type-hint all parameters and return types
- **Money is `decimal(10,2)`** — never `float`, never bare `integer`
- **Foreign keys are real**: `$table->foreignId('x')->constrained()->cascadeOnDelete()`
- Validation lives in **FormRequest** classes, not inline in controllers
- Business logic lives in **service** or **action** classes, not controllers
- Use **Eloquent** (`Model::create()`), not `Model::insert()` — the latter skips timestamps,
  casts and model events
- Authorisation via **Policies**; never trust `user_id` from a request
- Eager-load relationships to avoid N+1
- Every new feature ships with a feature test

---

## Phase 1 — Security  ✅ COMPLETE

Small, contained, and currently exploitable. Nothing else should be built on top of these bugs.

### [x] 1.1 Public registration grants admin rights

**Risk:** Critical. Anyone who registers at `/register` becomes an administrator.

`users.role` defaults to `'admin'` and `RegisterController::create()` never sets it.
`CustomerController::customerregister()` happens to pass `'customer'`, so the hole is only in the
Laravel UI path — but that path is publicly reachable.

- `database/migrations/2022_04_01_222217_add_fields_at_users.php:17` — change default to `'customer'`
- New migration to flip the column default **and** correct existing rows that were wrongly created as admin
- `app/Http/Controllers/Auth/RegisterController.php` — set `'role' => 'customer'` explicitly
- Consider a `UserRole` string-backed enum and cast it on the model

**Accept:** register a new account → `role` is `customer`; `/product` redirects to login.

### [x] 1.2 Hardcoded SMS credentials

**Risk:** Critical. Credentials are in git history.

- `app/Http/Controllers/CustomerController.php:31-47` — move URL, username and password to `.env`
- Add `SMS_API_URL`, `SMS_API_USERNAME`, `SMS_API_PASSWORD` to `.env.example` (empty values)
- Add a `config/services.php` entry; read via `config()`, never `env()` outside config files
- Replace raw cURL with Laravel's `Http::` client
- Move the send into a queued job so registration doesn't block on a third-party HTTP call
- Skip sending when credentials are absent, so local dev doesn't hit the live gateway

**Accept:** no secrets in the codebase; registration succeeds with SMS creds unset.

> **Rotate the credentials at the provider.** Removing them from the file does not invalidate
> them — they remain readable in git history.

### [x] 1.3 Price tampering in cart

**Risk:** Critical. The client sets the price it pays.

`CustomerController::insertcart()` stores `$request->product_current_price` (line 76) directly.

- Look the product up server-side and derive the price (discounted price if present, else regular)
- Ignore any price supplied in the request entirely

**Accept:** POST `insert/cart` with a forged `product_current_price` → the stored row holds the
true catalogue price.

### [x] 1.4 Cart IDOR — writing to another user's cart

**Risk:** High.

`insertcart()` takes `user_id` from the request (lines 59, 70, 80).

- Derive from `auth()->id()`
- Require authentication for the route

**Accept:** POST with someone else's `user_id` → the row is created against the *authenticated*
user, or is rejected.

### [x] 1.5 Cart IDOR — deleting another user's rows

**Risk:** High.

`cartremove()` is `Cart::find($request->cart_id)->delete();` (line 119) — no ownership check, and
it 500s on a bad ID.

- Scope the query to `auth()->id()`, use `findOrFail`, or add a `CartPolicy`

**Accept:** deleting another user's cart ID returns 403/404 and the row survives.

### [x] 1.6 No validation on customer registration

**Risk:** High. `customerregister()` inserts straight from the request.

- Add `CustomerRegisterRequest` — required name, unique email, password confirmed with a minimum
  length, phone format
- Apply the existing `propaganistas/laravel-disposable-email` rule to block throwaway addresses
- Hash via the model or `Hash::make()` consistently

**Accept:** duplicate email returns a validation error, not a 500 from the unique index.

### [x] 1.7 Unauthenticated cart/checkout routes

**Risk:** Medium.

`routes/web.php` declares no middleware at all; `cart()` checks `Auth::check()` by hand but
`insertcart`, `cartremove`, `setcountrycity` and `checkout` don't.

- Group the customer routes under `middleware('auth')`
- Keep browsing (`/`, product details) public

**Accept:** all cart/checkout endpoints redirect or 401 when logged out.

### [x] 1.8 Null-dereference 500s

**Risk:** Medium — trivially triggerable by a user.

- `FrontendController.php:165` — `Shipping::where(...)->first()->shipping_charge` fatals when the
  session has no country/city, i.e. any direct visit to `/checkout`
- `FrontendController.php:62` — `->first()->quantity` fatals for an unstocked combination
- `productdetails($slug)` — `->first()` returns null for an unknown slug

**Accept:** visiting `/checkout` with an empty session redirects to the cart with a message
instead of throwing.

---

## Phase 2 — Make the Store Sellable  ✅ COMPLETE

The core gap. Today the funnel ends at a totals page: no order is recorded, no stock moves,
nobody is notified. **This phase is the project.**

### [x] 2.1 Order schema

New migrations:

```
orders
  id
  user_id            foreignId constrained
  order_number       string unique        // human-facing, e.g. ORD-2026-000123
  status             string               // pending|paid|processing|shipped|delivered|cancelled
  subtotal           decimal(10,2)
  discount_total     decimal(10,2) default 0
  shipping_total     decimal(10,2) default 0
  grand_total        decimal(10,2)
  coupon_id          foreignId nullable constrained
  coupon_code        string nullable      // snapshot
  shipping_name      string
  shipping_phone     string
  shipping_address   text
  shipping_country_id foreignId constrained
  shipping_city      string
  placed_at          timestamp nullable
  timestamps

order_items
  id
  order_id           foreignId constrained cascadeOnDelete
  product_id         foreignId constrained
  color_id / size_id foreignId nullable constrained
  product_name       string               // snapshot
  sku                string               // snapshot
  unit_price         decimal(10,2)        // snapshot
  quantity           integer
  line_total         decimal(10,2)
  timestamps
```

**Snapshot every value shown to the customer.** If a product is renamed or repriced later, the
historical order must not change.

**Accept:** `migrate:fresh` runs clean; foreign keys visible in `SHOW CREATE TABLE`.

### [x] 2.2 Models

`Order`, `OrderItem` with relationships, an `OrderStatus` enum, and `casts` for the money columns.
Add `orders()` to `User`.

### [x] 2.3 Place-order transaction

The heart of the feature. `POST /checkout` → `PlaceOrderAction`.

Inside a single `DB::transaction()`:

1. Load the user's cart; abort if empty
2. Re-derive every price server-side — **never trust posted totals**
3. Re-validate the coupon (still valid, minimum met, limit remaining)
4. Re-read shipping charge for the chosen country/city
5. Lock inventory rows (`lockForUpdate()`) and verify sufficient stock
6. Create `orders` + `order_items` with snapshots
7. **Decrement inventory**
8. **Decrement `coupon_limit`**
9. Empty the cart
10. Dispatch confirmation notification (queued, outside the transaction)

**Edge cases that must be handled:** empty cart · stock changed since add-to-cart · coupon expired
or exhausted mid-session · concurrent orders for the last unit (this is why rows are locked) ·
double submit (idempotency).

**Accept:** placing an order creates the rows, reduces inventory by exactly the ordered quantity,
empties the cart, and decrements the coupon. Two concurrent orders for the last item → one
succeeds, one fails cleanly.

### [x] 2.4 Checkout form

`resources/views/checkout.blade.php` currently has **no `<form>` and no submit button** — it is
display-only.

- Add shipping name/phone/address fields, prefilled from the user profile
- Submit to the new POST route with CSRF
- Show validation errors
- Guard against double submission

### [x] 2.5 Order confirmation page

`GET /order/{order_number}` — scoped to the owner. Full breakdown and shipping details.

### [x] 2.6 Confirmation email

A queued `OrderPlaced` notification. This is the app's **first** use of Laravel mail — configure
`MAIL_*` and verify against Mailpit locally.

### [x] 2.7 Customer order history

Replace the static placeholder markup in `customer/customerdashboard.blade.php` with real orders:
list, status, totals, and a link to the detail view. Paginated.

### [x] 2.8 Admin order management

- Order list with status filter and search by order number
- Detail view with line items and customer info
- Status transitions (validated — no jumping from `pending` straight to `delivered`)
- Restock inventory on cancellation

---

## Phase 3 — Payments

### [x] 3.1 Choose a gateway
**SSLCommerz**, matching the Bangladeshi market and the existing SMS provider. bKash remains a
stub in `app/Payments/Gateways/BkashGateway.php`.

### [x] 3.2 `payments` table
`order_id`, `gateway`, `gateway_reference`, `amount`, `currency`, `status`, `payload` (json),
timestamps. Keep a full audit trail; never overwrite prior attempts.

### [x] 3.3 Payment flow
Initiate → redirect → callback, against the SSLCommerz v4 hosted checkout.

The IPN is the source of truth: its signature is verified before any outbound call, and every
callback — the browser return included — is re-validated against the gateway's validation API for
status, currency and amount before an order settles. `SettlePaymentAction` is the only writer of a
paid state and is idempotent under a row lock, so a duplicate IPN or a simultaneous admin action
cannot settle twice. A customer abandoning the redirect leaves the order pending and recoverable.

### [x] 3.3a Failed-payment recovery
Declines, validation mismatches and failed session requests are recorded in `payments_problem`
(nullable `order_id`, so a callback matching no order is still captured). Admins work the queue at
`/admin/problem-payments`, either re-checking with the gateway or recording an out-of-band payment
with a mandatory reference. Manual settlement uses the existing `paid` status; its origin survives
on `payments.gateway = 'manual'` and the resolved problem row. Customer cancellations are not
recorded — abandoning a payment page is normal, not a problem.

### [x] 3.4 Cash on delivery
A no-gateway path so the store can operate before the gateway is live.

### [ ] 3.5 Refunds
Partial and full, with an inventory restock decision.

---

## Phase 4 — Storefront Quality

- [ ] **4.1 Product search** — name, SKU, description
- [ ] **4.2 Pagination** — `FrontendController::index()` currently calls `Product::latest()->get()`,
      loading the entire catalogue on every homepage hit. Fix before the catalogue grows.
- [ ] **4.3 Filtering & sorting** — category, subcategory, price range, colour/size; sort by price
      and newest
- [ ] **4.4 Reviews & ratings** — verified-purchase only (requires Phase 2)
- [ ] **4.5 Wishlist**
- [ ] **4.6 Stock indicators** — "only 2 left", "out of stock", disable add-to-cart at zero
- [ ] **4.7 Cart badge** — live item count in the header

---

## Phase 5 — Hardening

- [x] **5.1 Checkout feature tests** — the highest-value tests in the codebase: order placement,
      stock decrement, coupon application, the concurrency case
- [ ] **5.2 Replace `Model::insert()`** — 11 call sites across 5 controllers bypass timestamps,
      casts and model events
- [ ] **5.3 Foreign keys & indexes** — retrofit onto existing tables; requires an integrity sweep
      for orphaned rows first
- [ ] **5.4 Money column types** — `products.regular_price` / `discounted_price` are `integer`;
      `shipping_charge` and `coupon_ammount` are `float`. Migrate to `decimal(10,2)`. Needs a
      careful data migration.
- [ ] **5.5 Queue side effects** — SMS and email off the request path; run a worker
- [ ] **5.6 Orphaned uploads** — deleting a product/category leaves its images on disk
- [ ] **5.7 Form requests everywhere** — several controllers still validate inline or not at all
- [ ] **5.8 Fix `coupon_ammount` misspelling** — rename column and references
- [ ] **5.9 Rename lowercase models** — `coupon` → `Coupon`, `product_featured_photo` →
      `ProductFeaturedPhoto`
- [ ] **5.10 Rate limiting** — login, registration and coupon-check endpoints
- [ ] **5.11 CI** — run `php artisan test` and `pint --test` on push

---

## Phase 6 — Growth

Order tracking with a status timeline · PDF invoices · admin sales analytics · multi-currency ·
richer product variants · abandoned-cart recovery · product recommendations · stock alerts ·
bulk CSV import/export · customer address book.

---

## Sequencing

```
Phase 1 (security)          ← start here, independent
   │
Phase 2 (orders)            ← the core deliverable
   ├── Phase 3 (payments)
   ├── Phase 4.4 (reviews — needs verified purchases)
   └── Phase 5.1 (checkout tests)

Phase 4 (storefront)        ← mostly parallel, independent of orders
Phase 5 (hardening)         ← ongoing
```

**Suggested first sprint:** all of Phase 1, then 2.1 → 2.4. That takes the store from "cannot
sell anything" to "can take a cash-on-delivery order", which is the point at which it becomes a
real shop.

---

## Open Questions

Answer before the phases they affect:

1. **Payment provider and target market?** Decides Phase 3 entirely.
2. **Guest checkout, or accounts only?** Currently accounts only; guest checkout changes the
   order schema (nullable `user_id` + contact details).
3. **Single or multi-currency?** Affects the money migration in 5.4 — cheaper to decide now.
4. **Is the SMS integration still wanted?** If yes it needs rotating credentials and a queue;
   if not, delete it in 1.2.
5. **Stock policy on cancellation** — automatic restock, or manual admin decision?
6. **Bootstrap 4 → 5?** Not required, but it is unmaintained and touches all 43 Blade files.
