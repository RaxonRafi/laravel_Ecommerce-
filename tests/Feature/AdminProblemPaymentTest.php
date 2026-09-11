<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\RecordPaymentProblemAction;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProblemReason;
use App\Enums\ProblemResolution;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentProblem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesOrders;
use Tests\TestCase;

class AdminProblemPaymentTest extends TestCase
{
    use MakesOrders;
    use RefreshDatabase;

    private const REFERENCE = 'ORD-2026-000001-ABC123';

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

    /** @param array<string, mixed> $overrides */
    private function fakeValidation(array $overrides = []): void
    {
        Http::fake(['*' => Http::response(array_merge([
            'status' => 'VALID',
            'tran_id' => self::REFERENCE,
            'amount' => '4250.00',
            'currency' => 'BDT',
        ], $overrides))]);
    }

    private function recorder(): RecordPaymentProblemAction
    {
        return app(RecordPaymentProblemAction::class);
    }

    /**
     * A declined payment on an order still waiting to be paid.
     *
     * @return array{0: PaymentProblem, 1: Order}
     */
    private function declinedProblem(): array
    {
        $order = $this->makeOrder();
        $this->makePendingPayment($order, self::REFERENCE);

        $problem = $this->recorder()->execute(
            order: $order,
            reason: ProblemReason::Declined,
            message: 'Card declined by issuing bank.',
            reference: self::REFERENCE,
            amount: 4250.00,
            currency: 'BDT',
        );

        return [$problem, $order];
    }

    // ------------------------------------------------------------- access

    public function test_a_guest_is_sent_to_login(): void
    {
        [$problem] = $this->declinedProblem();

        $this->get(route('admin.problem-payments.index'))->assertRedirect(route('login'));
        $this->post(route('admin.problem-payments.resolve', $problem), ['reference' => 'X'])
            ->assertRedirect(route('login'));
    }

    public function test_a_customer_is_refused(): void
    {
        [$problem, $order] = $this->declinedProblem();
        $customer = $this->makeCustomer();

        // The app turns customers away from every admin route by sending them back
        // to their own dashboard rather than showing a 403.
        $this->actingAs($customer)->get(route('admin.problem-payments.index'))
            ->assertRedirect('customer/dashboard');
        $this->actingAs($customer)->get(route('admin.problem-payments.show', $problem))
            ->assertRedirect('customer/dashboard');
        $this->actingAs($customer)
            ->post(route('admin.problem-payments.resolve', $problem), ['reference' => 'BKH1'])
            ->assertRedirect('customer/dashboard');
        $this->actingAs($customer)
            ->post(route('admin.problem-payments.recheck', $problem))
            ->assertRedirect('customer/dashboard');

        $this->assertFalse($problem->fresh()->isResolved());
        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);
    }

    // --------------------------------------------------------------- list

    public function test_the_list_shows_unresolved_problems_by_default(): void
    {
        [$problem, $order] = $this->declinedProblem();

        $resolved = $this->recorder()->execute(
            order: $this->makeOrder(),
            reason: ProblemReason::InitiationError,
            message: 'Gateway timed out.',
        );
        $resolved->update([
            'resolution' => ProblemResolution::Manual,
            'resolved_by' => $this->makeAdmin()->id,
            'resolved_at' => now(),
            'resolution_reference' => 'OLD-1',
        ]);

        $response = $this->actingAs($this->makeAdmin())->get(route('admin.problem-payments.index'));

        $response->assertOk();
        $response->assertSee($order->order_number);
        $response->assertDontSee('Gateway timed out.');
    }

    public function test_the_list_filters_by_reason(): void
    {
        [, $declinedOrder] = $this->declinedProblem();

        $otherOrder = $this->makeOrder();
        $this->recorder()->execute(
            order: $otherOrder,
            reason: ProblemReason::InitiationError,
            message: 'Gateway timed out before a session existed.',
        );

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.problem-payments.index', ['reason' => 'declined']));

        $response->assertOk();
        $response->assertSee($declinedOrder->order_number);
        $response->assertDontSee($otherOrder->order_number);
    }

    public function test_the_detail_page_shows_the_payload_and_links_to_the_order(): void
    {
        $order = $this->makeOrder();
        $problem = $this->recorder()->execute(
            order: $order,
            reason: ProblemReason::ValidationMismatch,
            message: 'Amount mismatch.',
            reference: self::REFERENCE,
            payload: ['bank_tran_id' => 'BANK-XYZ-1'],
        );

        $response = $this->actingAs($this->makeAdmin())->get(route('admin.problem-payments.show', $problem));

        $response->assertOk();
        $response->assertSee('BANK-XYZ-1');
        $response->assertSee(route('admin.orders.show', $order));
    }

    public function test_an_orderless_problem_still_renders(): void
    {
        $problem = $this->recorder()->execute(
            order: null,
            reason: ProblemReason::ValidationMismatch,
            message: 'No order matches transaction reference ORD-NOPE-000000-ZZZZZZ.',
            reference: 'ORD-NOPE-000000-ZZZZZZ',
        );

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.problem-payments.show', $problem))
            ->assertOk()
            ->assertSee('No order matches');
    }

    public function test_the_order_page_flags_unresolved_problems(): void
    {
        [, $order] = $this->declinedProblem();

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('unresolved payment problem')
            ->assertSee(route('admin.problem-payments.index'));
    }

    // ------------------------------------------------------------ recheck

    public function test_a_recheck_that_the_gateway_confirms_settles_the_order(): void
    {
        [$problem, $order] = $this->declinedProblem();
        $this->fakeValidation();

        $response = $this->actingAs($admin = $this->makeAdmin())
            ->from(route('admin.problem-payments.show', $problem))
            ->post(route('admin.problem-payments.recheck', $problem));

        $response->assertSessionHas('problem_success');

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Paid, $order->status);

        $payment = $order->payments()->where('status', PaymentStatus::Paid)->sole();
        $this->assertSame('sslcommerz', $payment->gateway);

        $problem->refresh();
        $this->assertTrue($problem->isResolved());
        $this->assertSame(ProblemResolution::GatewayRecheck, $problem->resolution);
        $this->assertSame($admin->id, $problem->resolved_by);
    }

    public function test_a_recheck_the_gateway_still_refuses_changes_nothing(): void
    {
        [$problem, $order] = $this->declinedProblem();
        $this->fakeValidation(['status' => 'INVALID_TRANSACTION']);

        $response = $this->actingAs($this->makeAdmin())
            ->from(route('admin.problem-payments.show', $problem))
            ->post(route('admin.problem-payments.recheck', $problem));

        $response->assertSessionHas('problem_error');

        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);
        $this->assertFalse($problem->fresh()->isResolved());
    }

    public function test_a_recheck_confirming_a_short_amount_changes_nothing(): void
    {
        [$problem, $order] = $this->declinedProblem();
        $this->fakeValidation(['amount' => '2000.00']);

        $response = $this->actingAs($this->makeAdmin())
            ->from(route('admin.problem-payments.show', $problem))
            ->post(route('admin.problem-payments.recheck', $problem));

        $response->assertSessionHas('problem_error');
        $this->assertStringContainsString('Amount mismatch', session('problem_error'));

        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);
        $this->assertFalse($problem->fresh()->isResolved());
    }

    public function test_a_recheck_when_the_gateway_is_unreachable_changes_nothing(): void
    {
        [$problem, $order] = $this->declinedProblem();
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timed out'));

        $response = $this->actingAs($this->makeAdmin())
            ->from(route('admin.problem-payments.show', $problem))
            ->post(route('admin.problem-payments.recheck', $problem));

        $response->assertSessionHas('problem_error');
        $this->assertStringContainsString('could not be reached', session('problem_error'));

        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);
        $this->assertFalse($problem->fresh()->isResolved());
    }

    public function test_a_problem_with_no_reference_cannot_be_rechecked(): void
    {
        $order = $this->makeOrder();
        $problem = $this->recorder()->execute(
            order: $order,
            reason: ProblemReason::InitiationError,
            message: 'Gateway timed out before a session existed.',
        );

        Http::fake();

        // The button is not offered...
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.problem-payments.show', $problem))
            ->assertOk()
            ->assertDontSee('Re-check with gateway');

        // ...and the endpoint refuses if called anyway.
        $this->actingAs($this->makeAdmin())
            ->from(route('admin.problem-payments.show', $problem))
            ->post(route('admin.problem-payments.recheck', $problem))
            ->assertSessionHas('problem_error');

        Http::assertNothingSent();
        $this->assertFalse($problem->fresh()->isResolved());
    }

    // ------------------------------------------------------- manual entry

    public function test_an_admin_can_record_a_payment_received_out_of_band(): void
    {
        [$problem, $order] = $this->declinedProblem();

        $response = $this->actingAs($admin = $this->makeAdmin())
            ->from(route('admin.problem-payments.show', $problem))
            ->post(route('admin.problem-payments.resolve', $problem), [
                'reference' => 'BKH8821X',
                'note' => 'Confirmed by phone, bKash from 01711000000.',
            ]);

        $response->assertSessionHas('problem_success');

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Paid, $order->status);

        $payment = $order->payments()->where('status', PaymentStatus::Paid)->sole();
        $this->assertSame('manual', $payment->gateway);
        $this->assertSame('BKH8821X', $payment->gateway_reference);
        $this->assertSame('4250.00', $payment->amount);

        $problem->refresh();
        $this->assertTrue($problem->isResolved());
        $this->assertSame(ProblemResolution::Manual, $problem->resolution);
        $this->assertSame($admin->id, $problem->resolved_by);
        $this->assertSame('BKH8821X', $problem->resolution_reference);
        $this->assertStringContainsString('Confirmed by phone', $problem->resolution_note);
    }

    public function test_a_manual_payment_without_a_reference_is_rejected(): void
    {
        [$problem, $order] = $this->declinedProblem();

        $response = $this->actingAs($this->makeAdmin())
            ->from(route('admin.problem-payments.show', $problem))
            ->post(route('admin.problem-payments.resolve', $problem), ['note' => 'No reference to hand']);

        $response->assertSessionHasErrors('reference');

        $order->refresh();
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame(0, $order->payments()->where('status', PaymentStatus::Paid)->count());
        $this->assertFalse($problem->fresh()->isResolved());
    }

    public function test_manual_settlement_stays_distinguishable_from_gateway_settlement(): void
    {
        [$problem, $order] = $this->declinedProblem();

        $this->actingAs($admin = $this->makeAdmin())
            ->post(route('admin.problem-payments.resolve', $problem), ['reference' => 'BKH8821X']);

        $order->refresh();

        // Same status as any other paid order...
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);

        // ...but the origin survives in two places.
        $this->assertSame('manual', $order->payments()->where('status', PaymentStatus::Paid)->sole()->gateway);
        $this->assertSame($admin->id, $problem->fresh()->resolved_by);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('recorded by an administrator');
    }

    // ------------------------------------------------ never settle twice

    public function test_an_already_paid_order_is_not_settled_again(): void
    {
        [$problem, $order] = $this->declinedProblem();

        // The IPN got there first.
        app(\App\Actions\SettlePaymentAction::class)->execute($order, self::REFERENCE, 4250.00);
        $this->assertSame(1, $order->fresh()->payments()->where('status', PaymentStatus::Paid)->count());

        $response = $this->actingAs($this->makeAdmin())
            ->from(route('admin.problem-payments.show', $problem))
            ->post(route('admin.problem-payments.resolve', $problem), ['reference' => 'BKH8821X']);

        $response->assertSessionHas('problem_error');
        $this->assertStringContainsString('already paid', session('problem_error'));

        $this->assertSame(1, Payment::where('order_id', $order->id)->where('status', PaymentStatus::Paid)->count());
    }

    public function test_an_already_resolved_problem_keeps_its_original_resolution(): void
    {
        [$problem, $order] = $this->declinedProblem();

        $this->actingAs($first = $this->makeAdmin())
            ->post(route('admin.problem-payments.resolve', $problem), ['reference' => 'FIRST-REF']);

        $problem->refresh();
        $this->assertSame('FIRST-REF', $problem->resolution_reference);

        $response = $this->actingAs($this->makeAdmin())
            ->from(route('admin.problem-payments.show', $problem))
            ->post(route('admin.problem-payments.resolve', $problem), ['reference' => 'SECOND-REF']);

        $response->assertSessionHas('problem_error');
        $this->assertStringContainsString('already been resolved', session('problem_error'));

        $problem->refresh();
        $this->assertSame('FIRST-REF', $problem->resolution_reference);
        $this->assertSame($first->id, $problem->resolved_by);
        $this->assertSame(1, Payment::where('order_id', $order->id)->where('status', PaymentStatus::Paid)->count());
    }

    public function test_two_administrators_resolving_at_once_settle_it_only_once(): void
    {
        [$problem, $order] = $this->declinedProblem();

        $adminA = $this->makeAdmin();
        $adminB = $this->makeAdmin();

        // Both hold the same unresolved record, as two browser tabs would.
        $this->actingAs($adminA)
            ->post(route('admin.problem-payments.resolve', $problem), ['reference' => 'A-REF']);

        $this->actingAs($adminB)
            ->post(route('admin.problem-payments.resolve', PaymentProblem::findOrFail($problem->id)), ['reference' => 'B-REF']);

        $paid = Payment::where('order_id', $order->id)->where('status', PaymentStatus::Paid)->get();
        $this->assertCount(1, $paid, 'Exactly one settlement may take effect.');

        $this->assertSame(1, PaymentProblem::resolved()->count());
        $this->assertSame('A-REF', $problem->fresh()->resolution_reference);
    }
}
