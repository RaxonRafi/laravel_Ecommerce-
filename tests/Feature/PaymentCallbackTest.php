<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProblemReason;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentProblem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\MakesOrders;
use Tests\TestCase;

class PaymentCallbackTest extends TestCase
{
    use MakesOrders;
    use RefreshDatabase;

    private const REFERENCE = 'ORD-2026-000001-ABC123';

    private const STORE_PASSWORD = 'testpass';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payment.gateways.sslcommerz.enabled' => true,
            'payment.gateways.sslcommerz.store_id' => 'teststore',
            'payment.gateways.sslcommerz.store_password' => self::STORE_PASSWORD,
            'payment.gateways.sslcommerz.sandbox' => true,
        ]);

        RateLimiter::clear('');
    }

    /** @param array<string, mixed> $overrides */
    private function fakeValidation(array $overrides = []): void
    {
        Http::fake(['*' => Http::response(array_merge([
            'status' => 'VALID',
            'tran_id' => self::REFERENCE,
            'amount' => '4250.00',
            'currency' => 'BDT',
            'bank_tran_id' => 'BANK123',
        ], $overrides))]);
    }

    /**
     * Signs a payload the way SSLCommerz does.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function signed(array $payload): array
    {
        $keys = array_keys($payload);
        sort($keys);

        $payload['verify_key'] = implode(',', $keys);

        $pairs = [];
        foreach ($keys as $key) {
            $pairs[] = $key.'='.$payload[$key];
        }
        $pairs[] = 'store_passwd='.md5(self::STORE_PASSWORD);
        sort($pairs);

        $payload['verify_sign'] = md5(implode('&', $pairs));

        return $payload;
    }

    /** @return array<string, mixed> */
    private function ipnPayload(): array
    {
        return $this->signed([
            'tran_id' => self::REFERENCE,
            'val_id' => 'val-1',
            'amount' => '4250.00',
            'currency' => 'BDT',
            'status' => 'VALID',
            'bank_tran_id' => 'BANK123',
            'card_type' => 'VISA',
        ]);
    }

    private function orderAwaitingPayment(): Order
    {
        $order = $this->makeOrder();
        $this->makePendingPayment($order, self::REFERENCE);

        return $order;
    }

    // ---------------------------------------------------------------- IPN

    public function test_the_ipn_accepts_a_request_with_no_session_or_csrf_token(): void
    {
        $order = $this->orderAwaitingPayment();
        $this->fakeValidation();

        $response = $this->postJson(route('payment.sslcommerz.ipn'), $this->ipnPayload());

        $response->assertOk();
        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
    }

    public function test_a_validated_notification_settles_the_order(): void
    {
        $order = $this->orderAwaitingPayment();
        $this->fakeValidation();

        $this->post(route('payment.sslcommerz.ipn'), $this->ipnPayload())->assertOk();

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Paid, $order->status);

        $payment = $order->payments()->where('status', PaymentStatus::Paid)->sole();
        $this->assertSame(self::REFERENCE, $payment->gateway_reference);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame(0, PaymentProblem::count());
    }

    public function test_a_duplicate_notification_changes_nothing_and_still_succeeds(): void
    {
        $order = $this->orderAwaitingPayment();
        $this->fakeValidation();

        $this->post(route('payment.sslcommerz.ipn'), $this->ipnPayload())->assertOk();
        $paidAt = $order->fresh()->payments()->where('status', PaymentStatus::Paid)->sole()->paid_at;

        $this->travel(2)->minutes();
        $second = $this->post(route('payment.sslcommerz.ipn'), $this->ipnPayload());

        $second->assertOk();
        $second->assertJson(['settled' => false]);

        $paid = Payment::where('order_id', $order->id)->where('status', PaymentStatus::Paid)->get();
        $this->assertCount(1, $paid);
        $this->assertEquals($paidAt, $paid->first()->paid_at);
        $this->assertSame(0, PaymentProblem::count());
    }

    public function test_a_bad_signature_is_rejected_without_contacting_the_gateway(): void
    {
        $order = $this->orderAwaitingPayment();
        Http::fake();

        $payload = $this->ipnPayload();
        $payload['verify_sign'] = str_repeat('0', 32);

        $response = $this->post(route('payment.sslcommerz.ipn'), $payload);

        $response->assertStatus(400);
        Http::assertNothingSent();

        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);

        $problem = PaymentProblem::sole();
        $this->assertSame(ProblemReason::ValidationMismatch, $problem->reason);
        $this->assertStringContainsString('signature', strtolower($problem->message));
    }

    public function test_an_amount_mismatch_is_rejected_and_recorded(): void
    {
        $order = $this->orderAwaitingPayment();
        $this->fakeValidation(['amount' => '1.00']);

        $this->post(route('payment.sslcommerz.ipn'), $this->ipnPayload())->assertStatus(400);

        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);

        $problem = PaymentProblem::sole();
        $this->assertSame(ProblemReason::ValidationMismatch, $problem->reason);
        $this->assertSame($order->id, $problem->order_id);
        $this->assertStringContainsString('Amount mismatch', $problem->message);
    }

    public function test_a_currency_mismatch_is_rejected_and_recorded(): void
    {
        $order = $this->orderAwaitingPayment();
        $this->fakeValidation(['currency' => 'USD']);

        $this->post(route('payment.sslcommerz.ipn'), $this->ipnPayload())->assertStatus(400);

        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);
        $this->assertStringContainsString('Currency mismatch', PaymentProblem::sole()->message);
    }

    public function test_an_invalid_transaction_is_rejected_and_recorded(): void
    {
        $order = $this->orderAwaitingPayment();
        $this->fakeValidation(['status' => 'INVALID_TRANSACTION']);

        $this->post(route('payment.sslcommerz.ipn'), $this->ipnPayload())->assertStatus(400);

        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);
        $this->assertSame(ProblemReason::ValidationMismatch, PaymentProblem::sole()->reason);
    }

    public function test_a_notification_for_an_unknown_order_is_rejected_and_recorded(): void
    {
        $this->fakeValidation();

        $payload = $this->signed([
            'tran_id' => 'ORD-NOPE-000000-ZZZZZZ',
            'val_id' => 'val-1',
            'amount' => '4250.00',
            'currency' => 'BDT',
            'status' => 'VALID',
        ]);

        $this->post(route('payment.sslcommerz.ipn'), $payload)->assertStatus(400);

        $problem = PaymentProblem::sole();
        $this->assertNull($problem->order_id);
        $this->assertSame(ProblemReason::ValidationMismatch, $problem->reason);
    }

    public function test_the_stored_payload_holds_no_signature_material(): void
    {
        $this->orderAwaitingPayment();
        $this->fakeValidation(['amount' => '1.00']);

        $this->post(route('payment.sslcommerz.ipn'), $this->ipnPayload());

        $payload = PaymentProblem::sole()->payload;
        $this->assertArrayNotHasKey('verify_sign', $payload);
        $this->assertArrayNotHasKey('verify_key', $payload);
    }

    public function test_the_ipn_endpoint_is_rate_limited(): void
    {
        $this->orderAwaitingPayment();
        $this->fakeValidation();

        $payload = $this->ipnPayload();

        // The limit is 60/minute; the 61st from one source must be turned away.
        for ($i = 0; $i < 60; $i++) {
            $this->post(route('payment.sslcommerz.ipn'), $payload);
        }

        $this->post(route('payment.sslcommerz.ipn'), $payload)->assertStatus(429);
    }

    // ------------------------------------------------------------ success

    public function test_the_success_return_settles_a_confirmed_payment(): void
    {
        $order = $this->orderAwaitingPayment();
        $this->fakeValidation();

        $response = $this->actingAs($order->user)
            ->post(route('payment.sslcommerz.success'), ['tran_id' => self::REFERENCE, 'val_id' => 'val-1']);

        $response->assertRedirect(route('order.show', $order));
        $response->assertSessionHas('order_success');
        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
    }

    public function test_a_forged_success_return_does_not_settle_the_order(): void
    {
        $order = $this->orderAwaitingPayment();
        $this->fakeValidation(['status' => 'INVALID_TRANSACTION']);

        $response = $this->actingAs($order->user)
            ->post(route('payment.sslcommerz.success'), ['tran_id' => self::REFERENCE]);

        $response->assertRedirect(route('order.show', $order));
        $response->assertSessionHas('order_warning');

        $order->refresh();
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame(0, $order->payments()->where('status', PaymentStatus::Paid)->count());
    }

    public function test_a_success_return_before_confirmation_leaves_the_order_pending(): void
    {
        $order = $this->orderAwaitingPayment();

        // The gateway has not made up its mind yet.
        $this->fakeValidation(['status' => 'PENDING']);

        $response = $this->actingAs($order->user)
            ->post(route('payment.sslcommerz.success'), ['tran_id' => self::REFERENCE, 'val_id' => 'val-1']);

        $response->assertSessionHas('order_warning');
        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);
    }

    public function test_a_success_return_for_an_unknown_reference_is_not_found(): void
    {
        Http::fake();

        $this->post(route('payment.sslcommerz.success'), ['tran_id' => 'ORD-NOPE-000000-ZZZZZZ'])
            ->assertNotFound();
    }

    public function test_a_customer_cannot_use_a_callback_to_reach_another_order(): void
    {
        $order = $this->orderAwaitingPayment();
        $this->fakeValidation();

        $intruder = $this->makeCustomer();

        $this->actingAs($intruder)
            ->post(route('payment.sslcommerz.success'), ['tran_id' => self::REFERENCE, 'val_id' => 'val-1'])
            ->assertNotFound();

        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);
    }

    // --------------------------------------------------------- fail/cancel

    public function test_a_failed_payment_is_recorded_and_the_order_survives(): void
    {
        $order = $this->orderAwaitingPayment();

        $response = $this->actingAs($order->user)->post(route('payment.sslcommerz.fail'), [
            'tran_id' => self::REFERENCE,
            'status' => 'FAILED',
            'error' => 'Card declined by issuing bank.',
            'amount' => '4250.00',
            'currency' => 'BDT',
        ]);

        $response->assertRedirect(route('order.show', $order));
        $response->assertSessionHas('order_error');

        $order->refresh();
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame(OrderStatus::Pending, $order->status);

        $problem = PaymentProblem::sole();
        $this->assertSame(ProblemReason::Declined, $problem->reason);
        $this->assertSame($order->id, $problem->order_id);
        $this->assertStringContainsString('Card declined', $problem->message);
    }

    public function test_a_cancelled_payment_records_nothing(): void
    {
        $order = $this->orderAwaitingPayment();

        $response = $this->actingAs($order->user)
            ->post(route('payment.sslcommerz.cancel'), ['tran_id' => self::REFERENCE]);

        $response->assertRedirect(route('order.show', $order));
        $response->assertSessionHas('order_warning');

        $order->refresh();
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame(0, PaymentProblem::count(), 'Cancelling is not a problem payment.');
    }
}
