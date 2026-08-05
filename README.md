# Goldfish eCommerce

A Laravel eCommerce application with a customer-facing storefront and an admin dashboard for
catalogue, inventory, shipping and coupon management.

**Status:** the admin/catalogue side is largely complete. The customer purchase funnel is
**not** — it currently dead-ends at the checkout summary page. See
[Feature Status](#feature-status) and the [Roadmap](#roadmap).

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13.24 |
| Language | PHP 8.4 |
| Database | MySQL 8.0 |
| Frontend build | Vite 8 |
| CSS/JS | Bootstrap 4.6, jQuery 3.7 |
| Auth | Laravel UI (scaffolding) + Sanctum (API tokens) |
| Images | Intervention Image v4 |
| Runtime | Docker + Docker Compose |

> PHP 8.4 is required. A local XAMPP PHP 8.0/8.1 install **cannot** run this project — use Docker.

---

## Quick Start

```bash
git clone <repo>
cd laravel_Ecommerce-
docker compose up -d
```

Then open **http://localhost:8000**.

The first startup is automatic: it creates `.env`, installs Composer and npm dependencies, waits
for MySQL, runs migrations, seeds the country list, builds frontend assets, and serves the app.

| Service | URL / Port |
|---|---|
| Application | http://localhost:8000 |
| Vite dev server | http://localhost:5173 |
| MySQL | `localhost:3307` (`goldfish` / `db_user` / `password`) |

### Common Commands

Everything runs inside the container:

```bash
docker compose up -d                              # start
docker compose down                               # stop
docker compose down -v                            # stop + drop the database
docker compose logs -f php                        # tail logs

docker compose exec php php artisan migrate
docker compose exec php php artisan route:list
docker compose exec php php artisan tinker
docker compose exec php php artisan test

docker compose exec php npm run dev               # Vite with hot reload
docker compose exec php npm run build             # production assets
```

### Accounts

There are no seeded users. Register at `/register` or `/login`.

There are two separate login entry points:

| Route | Audience |
|---|---|
| `/admin/login` | Staff / admin dashboard |
| `/login` | Customers (storefront) |

> ⚠️ The `users.role` column defaults to `'admin'`, and `RegisterController` never sets it — so
> **anyone registering via `/register` becomes an admin**. Convenient locally, unacceptable in
> production. See [Security](#security-issues).

---

## Domain Model

```
Category ──< Subcategory ──< Product ──< ProductFeaturedPhoto
                                 │
                                 └──< Inventory >── Color
                                          └──────── Size

User ──< Cart >── Product          Country ──< Shipping (city + charge)

Coupon (percentage | fixed, validity date, minimum order, usage limit)
```

Inventory is tracked per **product + colour + size** combination, which is what drives the
cascading colour → size → stock selector on the product detail page.

---

## Feature Status

### ✅ Working

**Admin**
- Authentication with role separation (`admin` / `customer`) via `CheckRole` middleware
- Category CRUD with photo upload, soft deletes, restore and force-delete
- Subcategory CRUD
- Product CRUD — slug, SKU, pricing, thumbnail, multiple featured photos
- Variations — colour and size management
- Per-variant inventory quantities
- Shipping zones — country + city + charge (249 countries seeded)
- Coupon creation — percentage or fixed, validity date, minimum order, usage limit
- Team member CRUD
- Profile management — name, password, avatar

**Storefront**
- Homepage with category and product listing
- Product detail page with related products and image gallery
- AJAX colour → size → live stock lookup
- Cart — add, view, remove
- Country/city selection driving shipping charge
- Coupon code validation with live total recalculation
- Checkout page showing subtotal, discount, shipping and grand total

### ⚠️ Incomplete

| Feature | What's missing |
|---|---|
| **Checkout** | Displays totals only. `checkout.blade.php` has **no form and no submit button**, and there is no `POST` route to place an order. |
| **Coupon usage limit** | `coupon_limit` is read but never decremented — coupons are effectively unlimited. |
| **Customer dashboard** | Static template markup; shows no real orders or account data. |
| **Email verification** | Scaffolded by Laravel UI but not enforced anywhere. |
| **SMS on registration** | Raw cURL call with hardcoded credentials; no error handling, blocks the request. |

### ❌ Missing Entirely

Verified absent from the codebase (no tables, models, routes or controllers):

- **Orders** — no `orders` or `order_items` tables, no order history, no admin order management
- **Payments** — no gateway, no payment records, no COD flow
- **Stock decrement** — inventory is never reduced when items are bought
- **Product search** — no search anywhere
- **Pagination** — `Product::latest()->get()` loads the entire catalogue on every page load
- **Reviews / ratings**
- **Wishlist**
- **Transactional email** — no `Mail::` or `Notification::` usage at all
- **Automated tests** — only the two stub examples
- **Invoices / receipts**
- **Admin analytics** — no sales reporting

---

## Critical Gap: The Purchase Funnel

This is the single most important thing to fix, and everything else depends on it.

```
Browse ✅ → Product ✅ → Cart ✅ → Shipping ✅ → Coupon ✅ → Totals ✅ → ✗ DEAD END
```

A customer can do everything right up to seeing their grand total, and then **there is no way to
place an order**. No order is recorded, no payment is taken, no stock moves, nobody is notified.
The store cannot currently sell anything.

---

## Security Issues

These were found during the audit and should be treated as blockers for any public deployment.

| # | Issue | Location | Risk |
|---|---|---|---|
| 1 | **Public registration grants admin** — `role` defaults to `'admin'` and is never overridden | `2022_04_01_222217_add_fields_at_users.php:17`, `RegisterController::create()` | Critical |
| 2 | **Hardcoded SMS API credentials** committed to git | `CustomerController.php:35-36` | Critical |
| 3 | **Price tampering** — `product_current_price` is taken from the request | `CustomerController::insertcart()` | Critical |
| 4 | **IDOR** — `user_id` comes from the request, so items can be added to any user's cart | `CustomerController::insertcart()` | High |
| 5 | **IDOR** — `cartremove()` deletes any cart row by ID with no ownership check | `CustomerController::cartremove()` | High |
| 6 | **No validation** on customer registration | `CustomerController::customerregister()` | High |
| 7 | Cart mutation endpoints are not behind `auth` middleware | `routes/web.php` | Medium |

> Credential rotation note: the SMS credentials in issue #2 are in git history. Changing the file
> is not enough — the credentials must be **rotated at the provider**.

---

## Data Model Debt

Worth addressing before the schema grows further:

- **No foreign keys anywhere.** All relationships are plain `integer` columns, so there is no
  referential integrity and no cascade behaviour.
- **Money stored as `integer` and `float`.** `regular_price`/`discounted_price` are integers
  (no minor units), while `shipping_charge` and `coupon_ammount` are floats. Floats must never be
  used for currency. Migrate to `decimal(10,2)` or integer minor units.
- **No indexes** on the de facto foreign key columns (`product_id`, `category_id`, …).
- **Inconsistent naming** — `coupon` and `product_featured_photo` models are lowercase;
  `coupon_ammount` is misspelled.
- **No `orders` table**, as above.

---

## Roadmap

Ordered so that each phase unblocks the next.

### Phase 1 — Security (do first)

Small, self-contained, and currently exploitable.

1. Default `users.role` to `'customer'`; set admin explicitly
2. Move SMS credentials to `.env` **and rotate them at the provider**
3. Derive cart price server-side from the product record
4. Derive `user_id` from `auth()->id()`, never the request
5. Scope cart deletion to the owner
6. Add validation to customer registration (FormRequest)
7. Put cart/checkout routes behind `auth` middleware

### Phase 2 — Make the Store Sellable

The minimum to actually take an order.

1. `orders` + `order_items` migrations, with foreign keys and `decimal` money columns
2. `Order` / `OrderItem` models and relationships
3. `POST /checkout` — place the order inside a DB transaction:
   - snapshot line items and prices
   - decrement inventory (guard against overselling)
   - decrement `coupon_limit`
   - clear the cart
4. Add the missing checkout form and address capture
5. Order confirmation page
6. Order confirmation email
7. Customer order history (replace the static dashboard)
8. Admin order list, detail view and status transitions

### Phase 3 — Payments

1. Choose a gateway (Stripe, SSLCommerz, bKash — depending on target market)
2. `payments` table with an audit trail
3. Redirect/callback flow with webhook verification
4. Cash on delivery as a fallback
5. Refund handling

### Phase 4 — Storefront Quality

1. Product search (name, SKU, description)
2. Pagination on catalogue pages — currently unbounded
3. Category and price filtering, plus sorting
4. Product reviews and ratings
5. Wishlist
6. Stock status on listings ("only 2 left", "out of stock")

### Phase 5 — Hardening

1. Feature tests for the checkout path — the highest-value tests in the app
2. Convert raw `Model::insert()` calls to Eloquent so timestamps and events fire
3. Add foreign keys and indexes
4. Fix money column types
5. Queue the SMS/email side effects instead of blocking the request
6. Image cleanup on delete (uploads currently leak)

### Phase 6 — Growth

Order tracking, invoices/PDF receipts, admin sales analytics, multi-currency,
product variants beyond colour/size, abandoned cart recovery, related-product recommendations.

---

## Project Layout

```
app/Http/Controllers/
  FrontendController      storefront — listing, product detail, coupon, checkout totals
  CustomerController      registration, cart, shipping selection
  HomeController          admin dashboard, profile, variations, shipping, coupons, team
  ProductController       product CRUD, featured photos, inventory
  CategoryController      category CRUD + soft deletes
  SubcategoryController   subcategory CRUD

resources/views/
  layouts/frontend_master   storefront theme
  layouts/dashboard_master  admin theme
  layouts/app               Laravel UI layout (password reset, team pages) — uses @vite
```

Uploads are written to `public/uploads/`. The storefront and dashboard themes are static assets
under `public/Frontend/` and `public/dashboard/`.

---

## Notes

- Bootstrap 4 emits Sass deprecation warnings during `npm run build`. These are expected and
  non-fatal; a Bootstrap 5 migration would touch all 43 Blade templates and should be its own task.
- Passwords are hashed manually in `RegisterController`, `CustomerController` and `HomeController`,
  so the `User` model deliberately does **not** use Laravel's `'password' => 'hashed'` cast.
  Adding it would double-hash and break every login.
