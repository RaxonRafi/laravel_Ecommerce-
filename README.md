# Goldfish eCommerce

A Laravel eCommerce application with a customer-facing storefront and an admin dashboard for
catalogue, inventory, shipping, coupon and order management.

**Status:** the store is functional end to end — customers can browse, add to cart, apply coupons
and **place orders**, and staff can manage those orders through to delivery. Online payment
gateways are scaffolded but not yet integrated; cash on delivery works today.

See [Feature Status](#feature-status) and the [Roadmap](#roadmap).

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
| Testing | PHPUnit 12 |
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

Everything runs inside the container — there is no PHP 8.4 on the host:

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

---

## Accounts & Admin Access

There are two separate login entry points:

| Route | Audience |
|---|---|
| `/admin/login` | Staff / admin dashboard |
| `/login` | Customers (storefront) |

**Self-registration always creates a customer.** `/register` and `/customer/register` both set
`role = 'customer'` explicitly, and the `users.role` column defaults to `'customer'`. There is
deliberately no way to obtain admin rights through the web UI.

### Creating the first administrator

```bash
docker compose exec php php artisan admin:create
```

Prompts for name, email and password, or accepts them as options:

```bash
docker compose exec php php artisan admin:create \
    --name="Site Admin" --email="admin@example.com" --password="secret12345"
```

### Adding or removing other administrators

Promote an existing account:

```bash
docker compose exec php php artisan admin:promote someone@example.com
docker compose exec php php artisan admin:promote someone@example.com --demote
```

The command refuses to demote the last remaining administrator, so you cannot lock yourself out.

> There is no admin user-management screen yet — see [Roadmap](#roadmap) Phase 4.

---

## Domain Model

```
Category ──< Subcategory ──< Product ──< ProductFeaturedPhoto
                                 │
                                 └──< Inventory >── Color
                                          └──────── Size

User ──< Cart >── Product          Country ──< Shipping (city + charge)

User ──< Order ──< OrderItem       Order ──< Payment
          └── Coupon (snapshotted)

Coupon (percentage | fixed, validity date, minimum order, usage limit)
```

Inventory is tracked per **product + colour + size** combination, which drives the cascading
colour → size → stock selector on the product detail page.

**Order lines are snapshots.** `product_name`, `sku`, `color_name`, `size_name` and `unit_price`
are copied onto `order_items` at purchase time, so renaming or repricing a product never rewrites
historical orders.

---

## Feature Status

### ✅ Working

**Storefront**
- Homepage with category and product listing
- Product detail page with related products and image gallery
- AJAX colour → size → live stock lookup
- Cart — add, view, remove (prices always derived server-side)
- Country/city selection driving shipping charge
- Coupon code validation with live total recalculation
- **Checkout with address capture and payment method selection**
- **Order placement** — transactional, with stock reservation
- **Order confirmation page and order history**

**Admin**
- Authentication with role separation (`admin` / `customer`) via `CheckRole` middleware
- Category CRUD with photo upload, soft deletes, restore and force-delete
- Subcategory CRUD
- Product CRUD — slug, SKU, pricing, thumbnail, multiple featured photos
- Variations — colour and size management
- Per-variant inventory quantities
- Shipping zones — country + city + charge (249 countries seeded)
- Coupon creation — percentage or fixed, validity date, minimum order, usage limit
- **Order management** — list with search and status filter, detail view, validated status
  transitions, automatic restock on cancellation
- Team member CRUD
- Profile management — name, password, avatar

### ⚠️ Partial

| Feature | State |
|---|---|
| **Payments** | Cash on delivery works. SSLCommerz and bKash are scaffolded with integration notes but not implemented — see [Payments](#payments). |
| **Order confirmation email** | The queued notification is written and tested, but **no email is actually delivered**: `MAIL_FROM_ADDRESS` is unset and there is no SMTP service in `docker-compose.yml`. Sending fails, is caught and logged, and the order still completes. Needs a mail service wired up. |
| **Email verification** | Scaffolded by Laravel UI but not enforced anywhere. |
| **SMS on registration** | Commented out in `CustomerController`. Credentials moved to config; needs rotating and a queued job before re-enabling. |
| **Test coverage** | Order placement is well covered. The rest of the app has none. |

### ❌ Still Missing

- **Product search** — no search anywhere
- **Pagination on the catalogue** — `Product::latest()->get()` still loads every product on the
  homepage
- **Reviews / ratings**
- **Wishlist**
- **Invoices / PDF receipts**
- **Admin analytics** — no sales reporting
- **Admin user management UI** — administrators are managed via artisan only

---

## Ordering Flow

```
Browse → Product → Cart → Shipping → Coupon → Checkout → ORDER PLACED → Confirmation
```

Order placement runs inside a single database transaction (`App\Actions\PlaceOrderAction`):

1. Load the cart; abort if empty
2. Re-derive every price from the catalogue — **posted totals are never trusted**
3. Re-validate the coupon (validity date, minimum order, remaining uses)
4. Re-read the shipping charge for the chosen destination
5. Lock inventory rows with `lockForUpdate()` and verify stock
6. Create the order and its snapshotted line items
7. Decrement inventory
8. Decrement the coupon's remaining uses
9. Clear the cart
10. Queue the confirmation notification

If any step fails the whole transaction rolls back: no order, no stock movement, and the cart is
left intact so the customer can correct the problem.

**Concurrency:** inventory rows are locked for the duration, so two customers racing for the last
unit produce one success and one clean failure rather than negative stock.

---

## Payments

Gateways sit behind `App\Payments\Contracts\PaymentGateway` and resolve through
`PaymentGatewayManager`. A gateway is only offered at checkout when it is **enabled and holds
credentials**, and `PlaceOrderRequest` validates the submitted method against that same list — so
an unconfigured gateway can never be selected, even by a crafted request.

| Gateway | State |
|---|---|
| Cash on delivery | Working. Payment is marked paid when an admin marks the order delivered. |
| SSLCommerz | Scaffolded — credentials + `charge()` implementation required |
| bKash | Scaffolded — credentials + `charge()` implementation required |

To enable one, fill in its credentials in `.env`, flip its `PAYMENT_*_ENABLED` flag, and implement
`charge()` in the corresponding class. Each stub carries step-by-step notes. No order code needs
to change.

> When implementing either gateway, treat the **server-side callback as the proof of payment**,
> never the browser redirect — a customer can close the tab, and a redirect URL can be forged.

Payment attempts are recorded in the `payments` table as an append-only audit trail; a failed
attempt followed by a successful one leaves two rows.

---

## Testing

```bash
docker compose exec php php artisan test
```

Tests run against a **separate `goldfish_test` database** because `RefreshDatabase` drops every
table.

> ⚠️ **Do not configure the test database through `phpunit.xml` alone.** Docker Compose exports
> `APP_ENV` and `DB_DATABASE` into the container; those land in `$_SERVER`, which Laravel's
> `ServerConstAdapter` reads *before* the `$_ENV` values PHPUnit sets — so the XML values are
> silently ignored, even with `force="true"`. The environment and database are therefore pinned
> programmatically in `tests/CreatesApplication.php`, which runs before `RefreshDatabase` touches
> anything, and refuses outright to run against a database whose name does not end in `_test`.
> This guard exists because the development database was destroyed twice before it was added.

---

## Data Model Debt

New tables (`orders`, `order_items`, `payments`) use real foreign keys and `decimal(10,2)` money.
The original tables do not:

- **No foreign keys on the original schema.** `products`, `carts`, `inventories`, `subcategories`
  and `shippings` relate through plain `integer` columns with no referential integrity.
- **Money stored as `integer` and `float`.** `products.regular_price` / `discounted_price` are
  integers; `shipping_charge` and `coupon_ammount` are floats. Floats must never be used for
  currency. Migrate to `decimal(10,2)`.
- **No indexes** on the de facto foreign key columns.
- **Inconsistent naming** — `coupon` and `product_featured_photo` models are lowercase;
  `coupon_ammount` is misspelled.
- **11 raw `Model::insert()` calls** across 5 controllers, which bypass timestamps, casts and
  model events.

---

## Roadmap

Detailed tasks, acceptance criteria and open questions are kept in `PLAN.md`, a local working
file that is not committed to the repository.

| Phase | Scope | State |
|---|---|---|
| 1 | Security — privilege escalation, price tampering, IDORs, validation | ✅ Complete |
| 2 | Orders — schema, transactional placement, confirmation, admin management | ✅ Complete |
| 3 | Payments — SSLCommerz or bKash integration, refunds | Scaffolded |
| 4 | Storefront — search, pagination, filtering, reviews, wishlist | Not started |
| 5 | Hardening — foreign keys, money types, Eloquent conversion, wider tests | Partly done |
| 6 | Growth — invoices, analytics, tracking, multi-currency | Not started |

**Suggested next step:** Phase 4's search and pagination. The homepage currently loads the entire
catalogue on every request, which will degrade as soon as the product count grows.

---

## Project Layout

```
app/
  Actions/PlaceOrderAction.php      the order-placement transaction
  Console/Commands/                 admin:create, admin:promote
  Enums/                            OrderStatus, PaymentStatus, PaymentMethod
  Exceptions/CheckoutException.php  user-facing checkout failures
  Payments/
    Contracts/PaymentGateway.php    the gateway interface
    Gateways/                       CashOnDelivery (working), SslCommerz, Bkash (stubs)
    PaymentGatewayManager.php       resolution + availability
  Http/Controllers/
    FrontendController              storefront — listing, product detail, coupon, checkout totals
    CustomerController              registration, cart, shipping selection
    OrderController                 order placement, confirmation, history
    Admin/OrderController           admin order list, detail, status transitions
    HomeController                  admin dashboard, profile, variations, shipping, coupons, team
    ProductController               product CRUD, featured photos, inventory
    CategoryController              category CRUD + soft deletes
    SubcategoryController           subcategory CRUD

resources/views/
  layouts/frontend_master   storefront theme
  layouts/dashboard_master  admin theme
  layouts/app               Laravel UI layout (password reset, team pages) — uses @vite
  orders/                   customer order confirmation and history
  admin/orders/             admin order management
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
- The SMS credentials that were previously hardcoded in `CustomerController` remain readable in
  git history. **They must be rotated at the provider** — removing them from the file is not enough.
