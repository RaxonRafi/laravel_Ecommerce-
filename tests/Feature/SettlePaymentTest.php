<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\SettlePaymentAction;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesOrders;
use Tests\TestCase;

class SettlePaymentTest extends TestCase
{
    use MakesOrders;
    use RefreshDatabase;

    private function settle(): SettlePaymentAction
    {
        return app(SettlePaymentAction::class);
    }

    public function test_it_settles_a_pending_order(): void
    {
        $order = $this->makeOrder();

        $outcome = $this->settle()->execute($order, 'ORD-2026-000001-ABC123', 4250.00);

        $this->assertTrue($outcome->tookEffect());

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Paid, $order->status);

        $payment = $order->payments()->where('status', PaymentStatus::Paid)->sole();
        $this->assertSame('sslcommerz', $payment->gateway);
        $this->assertSame('ORD-2026-000001-ABC123', $payment->gateway_reference);
        $this->assertSame('4250.00', $payment->amount);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_settling_twice_leaves_exactly_one_payment(): void
    {
        $order = $this->makeOrder();

        $first = $this->settle()->execute($order, 'ORD-2026-000001-ABC123', 4250.00);
        $originalPaidAt = $first->payment->paid_at;

        // The gateway redelivering the same notification a minute later.
        $this->travel(1)->minutes();
        $second = $this->settle()->execute($order->fresh(), 'ORD-2026-000001-ABC123', 4250.00);

        $this->assertTrue($first->tookEffect());
        $this->assertFalse($second->tookEffect());

        $paid = Payment::where('order_id', $order->id)->where('status', PaymentStatus::Paid)->get();
        $this->assertCount(1, $paid);
        $this->assertEquals($originalPaidAt, $paid->first()->paid_at);
    }

    public function test_it_does_not_drag_a_shipped_order_backwards(): void
    {
        $order = $this->makeOrder(['status' => OrderStatus::Shipped]);

        $this->settle()->execute($order, 'ORD-2026-000001-ABC123', 4250.00);

        $order->refresh();
        $this->assertSame(OrderStatus::Shipped, $order->status, 'A shipped order must not revert to paid.');
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
    }

    public function test_a_manual_settlement_is_marked_as_such(): void
    {
        $order = $this->makeOrder();

        $this->settle()->execute($order, 'BKH8821X', 4250.00, source: 'manual');

        $payment = $order->fresh()->payments()->sole();
        $this->assertSame('manual', $payment->gateway);
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
    }

    public function test_it_keeps_the_pending_attempt_as_an_audit_trail(): void
    {
        $order = $this->makeOrder();
        $this->makePendingPayment($order, 'ORD-2026-000001-ABC123');

        $this->settle()->execute($order, 'ORD-2026-000001-ABC123', 4250.00);

        $payments = $order->fresh()->payments;
        $this->assertCount(2, $payments, 'The failed/pending attempt must survive alongside the successful one.');
        $this->assertCount(1, $payments->where('status', PaymentStatus::Paid));
        $this->assertCount(1, $payments->where('status', PaymentStatus::Pending));
    }
}
