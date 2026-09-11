<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\RecordPaymentProblemAction;
use App\Enums\ProblemReason;
use App\Enums\ProblemResolution;
use App\Models\PaymentProblem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesOrders;
use Tests\TestCase;

class PaymentProblemRecordingTest extends TestCase
{
    use MakesOrders;
    use RefreshDatabase;

    private function action(): RecordPaymentProblemAction
    {
        return app(RecordPaymentProblemAction::class);
    }

    public function test_every_reason_and_resolution_round_trips(): void
    {
        foreach (['declined', 'validation_mismatch', 'initiation_error'] as $value) {
            $this->assertSame($value, ProblemReason::from($value)->value);
            $this->assertNotSame('', ProblemReason::from($value)->label());
            $this->assertNotSame('', ProblemReason::from($value)->badge());
        }

        foreach (['gateway_recheck', 'manual'] as $value) {
            $this->assertSame($value, ProblemResolution::from($value)->value);
            $this->assertNotSame('', ProblemResolution::from($value)->label());
            $this->assertNotSame('', ProblemResolution::from($value)->badge());
        }
    }

    public function test_a_problem_can_be_recorded_without_an_order(): void
    {
        $problem = $this->action()->execute(
            order: null,
            reason: ProblemReason::ValidationMismatch,
            message: 'No order matches transaction reference ORD-NOPE-000000-ZZZZZZ.',
            reference: 'ORD-NOPE-000000-ZZZZZZ',
        );

        $fresh = PaymentProblem::findOrFail($problem->id);

        $this->assertNull($fresh->order_id);
        $this->assertNull($fresh->order);
        $this->assertFalse($fresh->isResolved());
        $this->assertNull($fresh->resolution);
        $this->assertNull($fresh->resolved_by);
        $this->assertNull($fresh->resolved_at);
        $this->assertSame(ProblemReason::ValidationMismatch, $fresh->reason);
        $this->assertSame(1, PaymentProblem::unresolved()->count());
    }

    public function test_it_attaches_the_problem_to_its_order(): void
    {
        $order = $this->makeOrder();

        $problem = $this->action()->execute(
            order: $order,
            reason: ProblemReason::Declined,
            message: 'Card declined.',
            reference: 'ORD-2026-000001-ABC123',
            amount: 4250.00,
            currency: 'BDT',
        );

        $this->assertTrue($order->fresh()->paymentProblems->contains($problem));
        $this->assertSame('4250.00', $problem->fresh()->amount);
    }

    public function test_it_strips_credentials_and_signatures_from_the_stored_payload(): void
    {
        $problem = $this->action()->execute(
            order: null,
            reason: ProblemReason::ValidationMismatch,
            message: 'Signature did not verify.',
            payload: [
                'tran_id' => 'ORD-2026-000001-ABC123',
                'store_passwd' => 'super-secret',
                'verify_sign' => 'deadbeef',
                'verify_key' => 'amount,currency,tran_id',
                'nested' => [
                    'store_password' => 'also-secret',
                    'keep' => 'this',
                ],
            ],
        );

        $stored = $problem->fresh()->payload;

        $this->assertArrayNotHasKey('store_passwd', $stored);
        $this->assertArrayNotHasKey('verify_sign', $stored);
        $this->assertArrayNotHasKey('verify_key', $stored);
        $this->assertArrayNotHasKey('store_password', $stored['nested']);
        $this->assertSame('this', $stored['nested']['keep']);
        $this->assertSame('ORD-2026-000001-ABC123', $stored['tran_id']);

        // The raw row, not just the cast attribute.
        $this->assertStringNotContainsString('super-secret', (string) \DB::table('payments_problem')->value('payload'));
    }

    public function test_recheck_is_only_possible_with_a_gateway_reference(): void
    {
        $withReference = $this->action()->execute(
            order: $this->makeOrder(),
            reason: ProblemReason::Declined,
            message: 'Declined.',
            reference: 'ORD-2026-000002-ABC123',
        );

        $withoutReference = $this->action()->execute(
            order: $this->makeOrder(),
            reason: ProblemReason::InitiationError,
            message: 'Gateway timed out before a session existed.',
        );

        $this->assertTrue($withReference->canBeRecheckedWithGateway());
        $this->assertFalse($withoutReference->canBeRecheckedWithGateway());
    }
}
