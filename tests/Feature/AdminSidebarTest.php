<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\RecordPaymentProblemAction;
use App\Enums\ProblemReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesOrders;
use Tests\TestCase;

class AdminSidebarTest extends TestCase
{
    use MakesOrders;
    use RefreshDatabase;

    public function test_the_sidebar_links_to_orders_and_problem_payments(): void
    {
        $response = $this->actingAs($this->makeAdmin())->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertSee(route('admin.orders.index'));
        $response->assertSee(route('admin.problem-payments.index'));
        $response->assertSee('Problem Payments');
    }

    public function test_the_sidebar_counts_unresolved_problems(): void
    {
        $recorder = app(RecordPaymentProblemAction::class);

        $recorder->execute(
            order: $this->makeOrder(),
            reason: ProblemReason::Declined,
            message: 'Card declined.',
            reference: 'ORD-2026-000001-ABC123',
        );

        $resolved = $recorder->execute(
            order: $this->makeOrder(),
            reason: ProblemReason::Declined,
            message: 'Card declined.',
        );
        $resolved->update(['resolved_at' => now(), 'resolution' => 'manual']);

        $response = $this->actingAs($this->makeAdmin())->get(route('admin.orders.index'));

        // One unresolved, not two: a resolved problem is not outstanding work.
        $response->assertSee('badge badge-danger badge-sm ml-2">1<', false);
    }

    public function test_the_badge_is_absent_when_nothing_is_outstanding(): void
    {
        $response = $this->actingAs($this->makeAdmin())->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertDontSee('badge badge-danger badge-sm', false);
    }
}
