<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RecordPaymentProblemAction;
use App\Actions\SettlePaymentAction;
use App\Enums\PaymentStatus;
use App\Enums\ProblemReason;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Exceptions\SslCommerzRequestFailed;
use App\Payments\SslCommerz\IpnSignature;
use App\Payments\SslCommerz\SslCommerzValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Where SSLCommerz sends us after a customer pays.
 *
 * Four ways back in, and only one of them is evidence. The IPN is a
 * server-to-server call the gateway signs; the other three are the customer's own
 * browser, which can be pointed at any URL by anyone. So `success` re-validates
 * from scratch rather than believing what it was handed, and none of these
 * endpoints trusts its own request body.
 *
 * That is also why they are safe to exempt from CSRF: there is nothing here an
 * attacker can make a victim's browser do that the gateway would confirm.
 */
class PaymentCallbackController extends Controller
{
    public function __construct(
        private readonly SslCommerzValidator $validator,
        private readonly SettlePaymentAction $settle,
        private readonly RecordPaymentProblemAction $recordProblem,
        private readonly IpnSignature $signature,
    ) {}

    /**
     * The gateway's own notification. This is the authority on settlement.
     */
    public function ipn(Request $request): JsonResponse
    {
        $payload = $request->all();
        $transactionId = (string) $request->input('tran_id', '');

        if (! $this->signature->verify($payload)) {
            // Deliberately before any outbound call: an unsigned request must not be
            // able to make us query SSLCommerz.
            $this->recordProblem->execute(
                order: $this->orderFor($transactionId),
                reason: ProblemReason::ValidationMismatch,
                message: 'IPN signature did not verify.',
                reference: $transactionId ?: null,
                payload: $payload,
            );

            return response()->json(['status' => 'rejected', 'reason' => 'signature'], 400);
        }

        try {
            $outcome = $this->validator->validate(
                $transactionId,
                $request->input('val_id'),
                $payload,
            );
        } catch (SslCommerzRequestFailed $e) {
            $this->recordProblem->execute(
                order: $this->orderFor($transactionId),
                reason: ProblemReason::InitiationError,
                message: 'Could not validate the notification with the gateway: '.$e->getMessage(),
                reference: $transactionId ?: null,
                valId: $request->input('val_id'),
                payload: $payload,
            );

            // 500 so the gateway retries; the payment may well be good.
            return response()->json(['status' => 'error', 'reason' => 'validation_unavailable'], 500);
        }

        if (! $outcome->valid) {
            $this->recordProblem->execute(
                order: $outcome->order,
                reason: $outcome->reason ?? ProblemReason::ValidationMismatch,
                message: $outcome->message,
                reference: $transactionId ?: null,
                valId: $request->input('val_id'),
                amount: $outcome->amount,
                currency: $outcome->currency,
                payload: $outcome->payload,
            );

            return response()->json(['status' => 'rejected', 'reason' => 'validation'], 400);
        }

        $settlement = $this->settle->execute(
            $outcome->order,
            $transactionId,
            (float) $outcome->amount,
            payload: ['bank_tran_id' => $request->input('bank_tran_id'), 'card_type' => $request->input('card_type')],
        );

        // A redelivered notification is a success, not an error — answering 400
        // would only make the gateway send it again.
        return response()->json([
            'status' => 'ok',
            'settled' => $settlement->tookEffect(),
        ]);
    }

    /**
     * The customer's browser coming back from a completed payment.
     *
     * Validated from scratch. If it does not check out, the order simply stays
     * pending and the IPN can settle it later — this page never marks anything
     * paid on the strength of being visited.
     */
    public function success(Request $request): RedirectResponse
    {
        $transactionId = (string) $request->input('tran_id', '');
        $order = $this->orderFor($transactionId);

        if (! $order) {
            abort(404);
        }

        $this->authoriseCustomer($order);

        if ($order->payment_status === PaymentStatus::Paid) {
            return redirect()->route('order.show', $order)
                ->with('order_success', 'Thank you! Your payment has been received.');
        }

        try {
            $outcome = $this->validator->validate($transactionId, $request->input('val_id'), $request->all());
        } catch (SslCommerzRequestFailed $e) {
            Log::warning('Could not validate a success return', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('order.show', $order)
                ->with('order_warning', 'We are confirming your payment with the bank. Your order is safe — this page will show it as paid once the confirmation arrives.');
        }

        if (! $outcome->valid) {
            // Not recorded as a problem here: the IPN is the path that decides a
            // payment has genuinely failed, and it records it once. A forged or
            // premature return should not be able to fill the table.
            return redirect()->route('order.show', $order)
                ->with('order_warning', 'We have not been able to confirm your payment yet. Your order is safe — we will update it as soon as the bank confirms.');
        }

        $this->settle->execute($outcome->order, $transactionId, (float) $outcome->amount);

        return redirect()->route('order.show', $outcome->order)
            ->with('order_success', 'Thank you! Your payment has been received.');
    }

    /**
     * The gateway reached a decision and it was no.
     */
    public function fail(Request $request): RedirectResponse
    {
        $transactionId = (string) $request->input('tran_id', '');
        $order = $this->orderFor($transactionId);

        if (! $order) {
            abort(404);
        }

        $this->authoriseCustomer($order);

        $this->recordProblem->execute(
            order: $order,
            reason: ProblemReason::Declined,
            message: (string) ($request->input('error') ?: 'The gateway reported the payment as failed.'),
            reference: $transactionId,
            amount: $request->filled('amount') ? (float) $request->input('amount') : null,
            currency: $request->input('currency'),
            payload: $request->all(),
        );

        return redirect()->route('order.show', $order)
            ->with('order_error', 'Your payment did not go through. Your order is still reserved — you can arrange payment with us, or we will be in touch.');
    }

    /**
     * The customer changed their mind. An ordinary thing to do, and not a problem
     * to be worked through — nothing is recorded.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $order = $this->orderFor((string) $request->input('tran_id', ''));

        if (! $order) {
            abort(404);
        }

        $this->authoriseCustomer($order);

        return redirect()->route('order.show', $order)
            ->with('order_warning', 'Payment was cancelled, so nothing has been charged. Your order is still here whenever you want to pay.');
    }

    /**
     * Resolved through the reference we stored, never by parsing the value the
     * gateway echoed back at us.
     */
    private function orderFor(string $transactionId): ?Order
    {
        if ($transactionId === '') {
            return null;
        }

        return Payment::where('gateway_reference', $transactionId)->latest('id')->first()?->order;
    }

    /**
     * The browser-facing returns show an order page, so a signed-in customer must
     * not be able to reach someone else's order through one. The gateway posts
     * these back without a session, in which case there is nobody to check.
     */
    private function authoriseCustomer(Order $order): void
    {
        if (auth()->check() && $order->user_id !== auth()->id()) {
            abort(404);
        }
    }
}
