<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProblemReason;
use App\Payments\SslCommerz\SslCommerzValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesOrders;
use Tests\TestCase;

class SslCommerzValidatorTest extends TestCase
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

    private function validator(): SslCommerzValidator
    {
        return app(SslCommerzValidator::class);
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

    public function test_it_accepts_a_valid_transaction_for_the_right_amount(): void
    {
        $order = $this->makeOrder(['grand_total' => 4250.00]);
        $this->makePendingPayment($order, self::REFERENCE);
        $this->fakeValidation();

        $outcome = $this->validator()->validate(self::REFERENCE, 'val-1');

        $this->assertTrue($outcome->valid);
        $this->assertSame($order->id, $outcome->order->id);
        $this->assertSame(4250.00, $outcome->amount);
        $this->assertSame('BDT', $outcome->currency);
    }

    public function test_it_accepts_validated_as_well_as_valid(): void
    {
        $order = $this->makeOrder();
        $this->makePendingPayment($order, self::REFERENCE);
        $this->fakeValidation(['status' => 'VALIDATED']);

        $this->assertTrue($this->validator()->validate(self::REFERENCE, 'val-1')->valid);
    }

    public function test_it_rejects_an_amount_short_of_the_order_total(): void
    {
        $order = $this->makeOrder(['grand_total' => 4250.00]);
        $this->makePendingPayment($order, self::REFERENCE);
        $this->fakeValidation(['amount' => '4249.00']);

        $outcome = $this->validator()->validate(self::REFERENCE, 'val-1');

        $this->assertFalse($outcome->valid);
        $this->assertSame(ProblemReason::ValidationMismatch, $outcome->reason);
        $this->assertStringContainsString('4250.00', $outcome->message);
        $this->assertStringContainsString('4249.00', $outcome->message);
    }

    public function test_it_accepts_an_overpayment(): void
    {
        $order = $this->makeOrder(['grand_total' => 4250.00]);
        $this->makePendingPayment($order, self::REFERENCE);
        $this->fakeValidation(['amount' => '4250.50']);

        $this->assertTrue($this->validator()->validate(self::REFERENCE, 'val-1')->valid);
    }

    public function test_it_rejects_a_mismatched_currency(): void
    {
        $order = $this->makeOrder(['currency' => 'BDT']);
        $this->makePendingPayment($order, self::REFERENCE);
        $this->fakeValidation(['currency' => 'USD']);

        $outcome = $this->validator()->validate(self::REFERENCE, 'val-1');

        $this->assertFalse($outcome->valid);
        $this->assertSame(ProblemReason::ValidationMismatch, $outcome->reason);
        $this->assertStringContainsString('Currency mismatch', $outcome->message);
    }

    public function test_it_rejects_an_invalid_transaction(): void
    {
        $order = $this->makeOrder();
        $this->makePendingPayment($order, self::REFERENCE);
        $this->fakeValidation(['status' => 'INVALID_TRANSACTION']);

        $outcome = $this->validator()->validate(self::REFERENCE, 'val-1');

        $this->assertFalse($outcome->valid);
        $this->assertSame(ProblemReason::ValidationMismatch, $outcome->reason);
    }

    public function test_a_failed_transaction_is_reported_as_declined(): void
    {
        $order = $this->makeOrder();
        $this->makePendingPayment($order, self::REFERENCE);
        $this->fakeValidation(['status' => 'FAILED']);

        $outcome = $this->validator()->validate(self::REFERENCE, 'val-1');

        $this->assertFalse($outcome->valid);
        $this->assertSame(ProblemReason::Declined, $outcome->reason);
    }

    public function test_an_unknown_reference_is_rejected_without_asking_the_gateway(): void
    {
        Http::fake();

        $outcome = $this->validator()->validate('ORD-NOPE-000000-ZZZZZZ');

        $this->assertFalse($outcome->valid);
        $this->assertNull($outcome->order);
        $this->assertSame(ProblemReason::ValidationMismatch, $outcome->reason);
        Http::assertNothingSent();
    }

    public function test_it_queries_by_transaction_id_when_there_is_no_val_id(): void
    {
        $order = $this->makeOrder();
        $this->makePendingPayment($order, self::REFERENCE);

        // The transaction-query endpoint wraps its answer in `element`.
        Http::fake(['*' => Http::response([
            'element' => [[
                'status' => 'VALID',
                'tran_id' => self::REFERENCE,
                'amount' => '4250.00',
                'currency' => 'BDT',
            ]],
        ])]);

        $outcome = $this->validator()->validate(self::REFERENCE);

        $this->assertTrue($outcome->valid);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'merchantTransIDvalidationAPI.php'));
    }
}
