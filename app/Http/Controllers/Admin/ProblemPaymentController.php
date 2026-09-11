<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\SettlePaymentAction;
use App\Enums\PaymentStatus;
use App\Enums\ProblemReason;
use App\Enums\ProblemResolution;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveProblemPaymentRequest;
use App\Models\PaymentProblem;
use App\Payments\Exceptions\SslCommerzRequestFailed;
use App\Payments\SslCommerz\SslCommerzValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The queue of payments that failed and may still owe the shop money.
 *
 * Two ways out of it: ask the gateway again, or record that the money turned up
 * some other way. Both settle through SettlePaymentAction, so neither can double
 * up with an IPN arriving at the same moment.
 */
class ProblemPaymentController extends Controller
{
    public function __construct(
        private readonly SslCommerzValidator $validator,
        private readonly SettlePaymentAction $settle,
    ) {
        $this->middleware('auth');
        $this->middleware('checkrole');
    }

    public function index(Request $request): View
    {
        // Unresolved unless asked otherwise — the list is a to-do, not an archive.
        $state = $request->string('state')->toString() ?: 'unresolved';

        $problems = PaymentProblem::query()
            ->when($state === 'unresolved', fn ($q) => $q->unresolved())
            ->when($state === 'resolved', fn ($q) => $q->resolved())
            ->when($request->filled('reason'), fn ($q) => $q->where('reason', $request->string('reason')))
            ->with(['order', 'resolvedBy'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.problem-payments.index', [
            'problems' => $problems,
            'reasons' => ProblemReason::cases(),
            'state' => $state,
            'unresolvedCount' => PaymentProblem::unresolved()->count(),
        ]);
    }

    public function show(PaymentProblem $problemPayment): View
    {
        $problemPayment->load(['order.items', 'order.payments', 'order.user', 'resolvedBy']);

        return view('admin.problem-payments.show', ['problem' => $problemPayment]);
    }

    /**
     * Ask SSLCommerz again. Used when a payment probably did go through but the
     * notification never arrived or failed validation for a transient reason.
     */
    public function recheck(PaymentProblem $problemPayment): RedirectResponse
    {
        if ($guard = $this->refuseIfSettled($problemPayment)) {
            return $guard;
        }

        if (! $problemPayment->canBeRecheckedWithGateway()) {
            return back()->with('problem_error', 'There is no gateway transaction to re-check for this record.');
        }

        try {
            $outcome = $this->validator->validate(
                (string) $problemPayment->gateway_reference,
                $problemPayment->val_id,
            );
        } catch (SslCommerzRequestFailed $e) {
            return back()->with('problem_error', 'The gateway could not be reached, so nothing has been changed. Try again shortly.');
        }

        if (! $outcome->valid) {
            // Left unresolved on purpose: the gateway has not confirmed this money,
            // so the record still needs a human.
            return back()->with('problem_error', 'The gateway did not confirm this payment: '.$outcome->message);
        }

        $settlement = $this->settle->execute(
            $outcome->order,
            $problemPayment->gateway_reference,
            (float) $outcome->amount,
        );

        $this->markResolved($problemPayment, ProblemResolution::GatewayRecheck, (string) $problemPayment->gateway_reference);

        return back()->with('problem_success', $settlement->tookEffect()
            ? 'The gateway confirmed this payment. Order '.$outcome->order->order_number.' is now paid.'
            : 'The gateway confirmed this payment; the order was already paid.');
    }

    /**
     * Money arrived some other way — bank transfer, personal bKash, cash over the
     * counter — and an administrator is vouching for it.
     */
    public function resolveManually(ResolveProblemPaymentRequest $request, PaymentProblem $problemPayment): RedirectResponse
    {
        if ($guard = $this->refuseIfSettled($problemPayment)) {
            return $guard;
        }

        $order = $problemPayment->order;

        if (! $order) {
            return back()->with('problem_error', 'This record is not attached to an order, so there is nothing to settle.');
        }

        if ($order->payment_status === PaymentStatus::Paid) {
            return back()->with('problem_error', "Order {$order->order_number} is already paid, so no further payment has been recorded.");
        }

        $validated = $request->validated();

        $this->settle->execute(
            $order,
            $validated['reference'],
            (float) $order->grand_total,
            source: 'manual',
            payload: ['recorded_by' => $request->user()->id, 'note' => $validated['note'] ?? null],
        );

        $this->markResolved(
            $problemPayment,
            ProblemResolution::Manual,
            $validated['reference'],
            $validated['note'] ?? null,
        );

        return back()->with('problem_success', "Payment recorded. Order {$order->order_number} is now paid.");
    }

    /**
     * Both actions race the same two things: another admin clicking at the same
     * moment, and an IPN landing mid-click. The conditional update is what makes
     * "exactly one resolution takes effect" true rather than merely likely.
     */
    private function markResolved(
        PaymentProblem $problem,
        ProblemResolution $resolution,
        string $reference,
        ?string $note = null,
    ): bool {
        $claimed = DB::table('payments_problem')
            ->where('id', $problem->id)
            ->whereNull('resolved_at')
            ->update([
                'resolution' => $resolution->value,
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
                'resolution_reference' => $reference,
                'resolution_note' => $note,
                'updated_at' => now(),
            ]);

        return $claimed === 1;
    }

    private function refuseIfSettled(PaymentProblem $problem): ?RedirectResponse
    {
        if ($problem->isResolved()) {
            return back()->with('problem_error', 'This problem payment has already been resolved.');
        }

        return null;
    }
}
