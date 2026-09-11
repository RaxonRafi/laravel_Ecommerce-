<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProblemReason;
use App\Models\Order;
use App\Models\PaymentProblem;
use App\Payments\Gateways\SslCommerzGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesOrders;
use Tests\TestCase;

class SslCommerzCheckoutTest extends TestCase
{
    use MakesOrders;
    use RefreshDatabase;

    private const PAGE_URL = 'https://sandbox.sslcommerz.com/EasyCheckOut/testcde5f3b2a';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payment.gateways.sslcommerz.enabled' => true,
            'payment.gateways.sslcommerz.store_id' => 'teststore',
            'payment.gateways.sslcommerz.store_password' => 'testpass',
            'payment.gateways.sslcommerz.sandbox' => true,
        ]);
    }

    private function fakeSession(): void
    {
        Http::fake(['*' => Http::response([
            'status' => 'SUCCESS',
            'GatewayPageURL' => self::PAGE_URL,
            'sessionkey' => 'sess-123',
        ])]);
    }

    private function gateway(): SslCommerzGateway
    {
        return app(SslCommerzGateway::class);
    }

    public function test_it_returns_a_redirect_to_the_hosted_payment_page(): void
    {
        $this->fakeSession();
        $order = $this->makeOrder();
        // The pending row PlaceOrderAction writes, before any reference exists.
        \App\Models\Payment::create([
            'order_id' => $order->id,
            'gateway' => 'sslcommerz',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'status' => PaymentStatus::Pending,
        ]);

        $result = $this->gateway()->charge($order);

        $this->assertTrue($result->requiresRedirect());
        $this->assertSame(self::PAGE_URL, $result->redirectUrl);

        $order->refresh();
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);

        $payment = $order->payments()->sole();
        $this->assertSame($result->reference, $payment->gateway_reference);
        $this->assertSame('sslcommerz', $payment->gateway);
    }

    public function test_each_attempt_gets_its_own_reference(): void
    {
        $this->fakeSession();
        $order = $this->makeOrder();

        $first = $this->gateway()->charge($order);
        $second = $this->gateway()->charge($order->fresh());

        $this->assertNotSame($first->reference, $second->reference);

        foreach ([$first, $second] as $result) {
            $this->assertLessThanOrEqual(30, strlen((string) $result->reference));
            $this->assertStringStartsWith($order->order_number.'-', (string) $result->reference);
        }

        // Each reference resolves to exactly one order.
        $this->assertSame(1, $order->payments()->where('gateway_reference', $first->reference)->count());
        $this->assertSame(1, $order->payments()->where('gateway_reference', $second->reference)->count());
    }

    public function test_the_amount_sent_comes_from_the_order_not_the_request(): void
    {
        $this->fakeSession();
        $order = $this->makeOrder(['grand_total' => 4250.00]);

        // Whatever a request might have carried, the gateway is told the stored total.
        request()->merge(['amount' => '1.00', 'total_amount' => '1.00', 'currency' => 'USD']);

        $this->gateway()->charge($order);

        Http::assertSent(fn (Request $request) => $request['total_amount'] === '4250.00'
            && $request['currency'] === 'BDT');
    }

    public function test_the_callback_urls_are_sent_to_the_gateway(): void
    {
        $this->fakeSession();

        $this->gateway()->charge($this->makeOrder());

        Http::assertSent(fn (Request $r) => $r['ipn_url'] === route('payment.sslcommerz.ipn')
            && $r['success_url'] === route('payment.sslcommerz.success')
            && $r['fail_url'] === route('payment.sslcommerz.fail')
            && $r['cancel_url'] === route('payment.sslcommerz.cancel'));
    }

    public function test_an_unconfigured_gateway_refuses_to_charge(): void
    {
        config(['payment.gateways.sslcommerz.store_password' => '']);

        $this->expectException(\App\Payments\Exceptions\GatewayNotConfigured::class);

        $this->gateway()->charge($this->makeOrder());
    }

    public function test_a_timeout_leaves_the_order_alive_and_records_a_problem(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

        $order = $this->makeOrder();

        try {
            $this->gateway()->charge($order);
            $this->fail('Expected the gateway to report the failed request.');
        } catch (\App\Payments\Exceptions\SslCommerzRequestFailed $e) {
            app(\App\Actions\RecordPaymentProblemAction::class)->execute(
                order: $order,
                reason: ProblemReason::InitiationError,
                message: $e->getMessage(),
            );
        }

        $order->refresh();
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);

        $problem = PaymentProblem::sole();
        $this->assertSame(ProblemReason::InitiationError, $problem->reason);
        $this->assertSame($order->id, $problem->order_id);
        $this->assertFalse($problem->isResolved());
    }

    public function test_the_order_survives_a_failed_session_at_checkout(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

        $customer = $this->makeCustomer();
        $countryId = $this->placeableCart($customer);

        $response = $this->actingAs($customer)
            ->withSession(['s_country_id' => $countryId, 's_city_name' => 'Dhaka'])
            ->post(route('order.place'), [
                'shipping_name' => 'Test Customer',
                'shipping_phone' => '01711000000',
                'shipping_address' => '12 Test Road',
                'payment_method' => 'sslcommerz',
            ]);

        $order = Order::sole();

        // Not an error page: the customer is shown their order.
        $response->assertRedirect(route('order.show', $order));
        $response->assertSessionHas('order_warning');

        $this->assertSame(PaymentStatus::Pending, $order->payment_status);

        $problem = PaymentProblem::sole();
        $this->assertSame(ProblemReason::InitiationError, $problem->reason);
        $this->assertSame($order->id, $problem->order_id);
    }

    /**
     * A cart with one in-stock line and a shipping destination, ready to check out.
     */
    private function placeableCart(\App\Models\User $customer): int
    {
        $countryId = $this->makeCountry();

        \DB::table('shippings')->insert([
            'country_id' => $countryId, 'city_name' => 'Dhaka', 'shipping_charge' => 250,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $categoryId = \DB::table('categories')->insertGetId([
            'category_name' => 'Apparel', 'created_by' => 1, 'category_photo' => 'x.jpg',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $subcategoryId = \DB::table('subcategories')->insertGetId([
            'category_id' => $categoryId, 'subcategory_name' => 'Shirts', 'added_by' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $colorId = \DB::table('colors')->insertGetId([
            'color_name' => 'Blue', 'color_code' => '#00f', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $sizeId = \DB::table('sizes')->insertGetId([
            'size_name' => 'M', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $productId = \DB::table('products')->insertGetId([
            'product_name' => 'Classic Shirt', 'regular_price' => 4000, 'discounted_price' => 4000,
            'slug' => 'classic-shirt', 'short_description' => 's', 'sku' => 'SH-001',
            'category_id' => $categoryId, 'subcategory_id' => $subcategoryId,
            'long_description' => 'l', 'created_at' => now(), 'updated_at' => now(),
        ]);
        \DB::table('inventories')->insert([
            'product_id' => $productId, 'color_id' => $colorId, 'size_id' => $sizeId,
            'quantity' => 5, 'created_at' => now(), 'updated_at' => now(),
        ]);
        \App\Models\Cart::create([
            'user_id' => $customer->id, 'product_id' => $productId,
            'product_current_price' => 4000,
            'color_id' => $colorId, 'size_id' => $sizeId, 'cart_amount' => 1,
        ]);

        return $countryId;
    }
}
