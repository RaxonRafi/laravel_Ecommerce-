<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\MakesOrders;
use Tests\TestCase;

/**
 * A gateway missing its credentials must be invisible at checkout AND unusable by
 * a crafted request — the checkout page is not the security boundary.
 */
class SslCommerzAvailabilityTest extends TestCase
{
    use MakesOrders;
    use RefreshDatabase;

    private int $countryId;

    private int $productId;

    private int $colorId;

    private int $sizeId;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->countryId = $this->makeCountry();

        DB::table('shippings')->insert([
            'country_id' => $this->countryId, 'city_name' => 'Dhaka', 'shipping_charge' => 60,
            'created_at' => now(), 'updated_at' => now(),
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
    }

    private function configureGateway(bool $withPassword = true): void
    {
        config([
            'payment.gateways.sslcommerz.enabled' => true,
            'payment.gateways.sslcommerz.store_id' => 'teststore',
            'payment.gateways.sslcommerz.store_password' => $withPassword ? 'testpass' : '',
            'payment.gateways.sslcommerz.sandbox' => true,
        ]);
    }

    private function customerWithCart(): User
    {
        $user = $this->makeCustomer();

        Cart::create([
            'product_id' => $this->productId,
            'product_current_price' => 800,
            'color_id' => $this->colorId,
            'size_id' => $this->sizeId,
            'cart_amount' => 1,
            'user_id' => $user->id,
        ]);

        return $user;
    }

    public function test_it_is_offered_at_checkout_when_fully_configured(): void
    {
        $this->configureGateway();

        $response = $this->actingAs($this->customerWithCart())
            ->withSession(['s_country_id' => $this->countryId, 's_city_name' => 'Dhaka'])
            ->get(route('checkout'));

        $response->assertOk();
        $response->assertSee('SSLCommerz');
        $response->assertSee('value="sslcommerz"', false);
    }

    public function test_it_is_hidden_at_checkout_when_the_store_password_is_blank(): void
    {
        $this->configureGateway(withPassword: false);

        $response = $this->actingAs($this->customerWithCart())
            ->withSession(['s_country_id' => $this->countryId, 's_city_name' => 'Dhaka'])
            ->get(route('checkout'));

        $response->assertOk();
        $response->assertDontSee('value="sslcommerz"', false);
    }

    public function test_a_crafted_request_cannot_select_an_unconfigured_gateway(): void
    {
        $this->configureGateway(withPassword: false);

        $response = $this->actingAs($this->customerWithCart())
            ->withSession(['s_country_id' => $this->countryId, 's_city_name' => 'Dhaka'])
            ->post(route('order.place'), [
                'shipping_name' => 'Jane Buyer',
                'shipping_phone' => '01711000000',
                'shipping_address' => '12 Test Road',
                'payment_method' => 'sslcommerz',
            ]);

        $response->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Order::count(), 'No order may be created from a rejected payment method.');
    }
}
