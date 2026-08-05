<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderPlacementTest extends TestCase
{
    use RefreshDatabase;

    private int $productId;
    private int $colorId;
    private int $sizeId;
    private int $countryId;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->countryId = (int) DB::table('countries')->insertGetId([
            'code' => 'BD', 'name' => 'Bangladesh', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $categoryId = DB::table('categories')->insertGetId([
            'category_name' => 'Apparel', 'created_by' => 1, 'category_photo' => 'x.jpg',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $subcategoryId = DB::table('subcategories')->insertGetId([
            'category_id' => $categoryId, 'subcategory_name' => 'Shirts', 'added_by' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->colorId = (int) DB::table('colors')->insertGetId([
            'color_name' => 'Red', 'color_code' => '#f00', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->sizeId = (int) DB::table('sizes')->insertGetId([
            'size_name' => 'M', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->productId = (int) DB::table('products')->insertGetId([
            'product_name' => 'Classic Shirt', 'regular_price' => 1000, 'discounted_price' => 800,
            'slug' => 'classic-shirt', 'short_description' => 's', 'sku' => 'SH-001',
            'category_id' => $categoryId, 'subcategory_id' => $subcategoryId,
            'long_description' => 'l', 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('inventories')->insert([
            'product_id' => $this->productId, 'color_id' => $this->colorId, 'size_id' => $this->sizeId,
            'quantity' => 10, 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('shippings')->insert([
            'country_id' => $this->countryId, 'city_name' => 'Dhaka', 'shipping_charge' => 60,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function customerWithCart(int $quantity = 2): User
    {
        $user = User::create([
            'name' => 'Jane Buyer', 'email' => 'jane@example.com',
            'password' => bcrypt('secret12345'), 'role' => 'customer',
            'phone_number' => '017', 'address' => 'addr',
        ]);

        Cart::create([
            'product_id' => $this->productId,
            'product_current_price' => 800,
            'color_id' => $this->colorId,
            'size_id' => $this->sizeId,
            'cart_amount' => $quantity,
            'user_id' => $user->id,
        ]);

        return $user;
    }

    /**
     * @return array<string, string>
     */
    private function payload(): array
    {
        return [
            'shipping_name' => 'Jane Buyer',
            'shipping_phone' => '01712345678',
            'shipping_address' => '42 Green Road',
            'payment_method' => 'cod',
        ];
    }

    private function withDestination(): static
    {
        return $this->withSession([
            's_country_id' => $this->countryId,
            's_city_name' => 'Dhaka',
        ]);
    }

    public function test_it_places_an_order_and_records_correct_totals(): void
    {
        $user = $this->customerWithCart(2);

        $this->actingAs($user)
            ->withDestination()
            ->post(route('order.place'), $this->payload())
            ->assertRedirect();

        $order = Order::first();

        $this->assertNotNull($order);
        $this->assertSame('1600.00', $order->subtotal);      // 2 x 800 discounted price
        $this->assertSame('0.00', $order->discount_total);
        $this->assertSame('60.00', $order->shipping_total);
        $this->assertSame('1660.00', $order->grand_total);
        $this->assertSame(OrderStatus::Pending, $order->status);
    }

    public function test_it_snapshots_product_details_onto_the_order_line(): void
    {
        $user = $this->customerWithCart(1);

        $this->actingAs($user)->withDestination()->post(route('order.place'), $this->payload());

        $item = Order::first()->items->first();

        $this->assertSame('Classic Shirt', $item->product_name);
        $this->assertSame('SH-001', $item->sku);
        $this->assertSame('Red', $item->color_name);
        $this->assertSame('M', $item->size_name);
        $this->assertSame('800.00', $item->unit_price);
    }

    public function test_it_decrements_inventory_and_clears_the_cart(): void
    {
        $user = $this->customerWithCart(3);

        $this->actingAs($user)->withDestination()->post(route('order.place'), $this->payload());

        $this->assertSame(7, (int) DB::table('inventories')->where('product_id', $this->productId)->value('quantity'));
        $this->assertSame(0, Cart::where('user_id', $user->id)->count());
    }

    public function test_it_prices_from_the_catalogue_not_the_cart_row(): void
    {
        $user = $this->customerWithCart(1);

        // Simulate a stale or tampered cart row holding a price of 1.
        Cart::where('user_id', $user->id)->update(['product_current_price' => 1]);

        $this->actingAs($user)->withDestination()->post(route('order.place'), $this->payload());

        $this->assertSame('800.00', Order::first()->items->first()->unit_price);
        $this->assertSame('800.00', Order::first()->subtotal);
    }

    public function test_it_applies_a_valid_coupon_and_consumes_one_use(): void
    {
        $user = $this->customerWithCart(2);

        DB::table('coupons')->insert([
            'coupon_name' => 'SAVE10', 'coupon_validity_date' => now()->addYear(),
            'coupon_type' => 'Percentage', 'coupon_ammount' => 10, 'minimum_order' => 100,
            'coupon_limit' => 5, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession([
                's_country_id' => $this->countryId,
                's_city_name' => 'Dhaka',
                's_coupon_name' => 'SAVE10',
            ])
            ->post(route('order.place'), $this->payload());

        $order = Order::first();

        $this->assertSame('160.00', $order->discount_total);          // 10% of 1600
        $this->assertSame('1500.00', $order->grand_total);            // 1600 - 160 + 60
        $this->assertSame('SAVE10', $order->coupon_code);
        $this->assertSame(4, (int) DB::table('coupons')->where('coupon_name', 'SAVE10')->value('coupon_limit'));
    }

    public function test_it_ignores_an_exhausted_coupon(): void
    {
        $user = $this->customerWithCart(2);

        DB::table('coupons')->insert([
            'coupon_name' => 'GONE', 'coupon_validity_date' => now()->addYear(),
            'coupon_type' => 'Percentage', 'coupon_ammount' => 50, 'minimum_order' => 0,
            'coupon_limit' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession([
                's_country_id' => $this->countryId,
                's_city_name' => 'Dhaka',
                's_coupon_name' => 'GONE',
            ])
            ->post(route('order.place'), $this->payload());

        $this->assertSame('0.00', Order::first()->discount_total);
    }

    public function test_it_refuses_to_oversell_and_changes_nothing(): void
    {
        $user = $this->customerWithCart(99);

        $this->actingAs($user)
            ->withDestination()
            ->post(route('order.place'), $this->payload())
            ->assertRedirect(route('cart'))
            ->assertSessionHas('checkout_error');

        $this->assertSame(0, Order::count());
        $this->assertSame(10, (int) DB::table('inventories')->where('product_id', $this->productId)->value('quantity'));
        $this->assertSame(1, Cart::where('user_id', $user->id)->count());
    }

    public function test_it_rejects_checkout_with_an_empty_cart(): void
    {
        $user = $this->customerWithCart(1);
        Cart::query()->delete();

        $this->actingAs($user)
            ->withDestination()
            ->post(route('order.place'), $this->payload())
            ->assertRedirect(route('cart'));

        $this->assertSame(0, Order::count());
    }

    public function test_it_rejects_an_unavailable_payment_method(): void
    {
        $user = $this->customerWithCart(1);

        $this->actingAs($user)
            ->withDestination()
            // bkash is scaffolded but unconfigured, so it must not be selectable.
            ->post(route('order.place'), array_merge($this->payload(), ['payment_method' => 'bkash']))
            ->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Order::count());
    }

    public function test_a_customer_cannot_view_another_customers_order(): void
    {
        $user = $this->customerWithCart(1);
        $this->actingAs($user)->withDestination()->post(route('order.place'), $this->payload());

        $order = Order::first();

        $intruder = User::create([
            'name' => 'Nosey', 'email' => 'nosey@example.com',
            'password' => bcrypt('secret12345'), 'role' => 'customer',
        ]);

        $this->actingAs($intruder)->get(route('order.show', $order))->assertNotFound();
        $this->actingAs($user)->get(route('order.show', $order))->assertOk();
    }

    public function test_guests_cannot_place_orders(): void
    {
        $this->post(route('order.place'), $this->payload())->assertRedirect();
        $this->assertSame(0, Order::count());
    }
}
