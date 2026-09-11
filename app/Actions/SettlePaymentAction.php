<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * The only thing in the application that marks an order paid.
 *
 * Four callers can settle an order — the IPN, the customer returning to the
 * success page, an admin re-checking with the gateway, and an admin recording an
 * out-of-band payment. If each did its own "is it paid yet?" check, a duplicate
 * IPN landing while an admin clicks resolve would settle it twice. Instead they
 * all come here, where the order row is locked and the question is asked once.
 */
class SettlePaymentAction
{
    /**
     * @param  string  $source  'sslcommerz' when the gateway confirmed it, 'manual' when
     *                          an administrator did. Stored on the payment row, which is how
     *                          reconciliation tells gateway money from keyed-in money.
     * @param  array<string, mixed>  $payload
     */
    public function execute(
        Order $order,
        ?string $reference,
        float $amount,
        string $source = 'sslcommerz',
        array $payload = [],
    ): SettlementOutcome {
        return DB::transaction(function () use ($order, $reference, $amount, $source, $payload): SettlementOutcome {
            // Serialises concurrent settlements of this order against each other.
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->payment_status === PaymentStatus::Paid) {
                return SettlementOutcome::alreadySettled();
            }

            $payment = Payment::create([
                'order_id' => $locked->id,
                'gateway' => $source,
                'gateway_reference' => $reference,
                'amount' => $amount,
                'currency' => $locked->currency,
                'status' => PaymentStatus::Paid,
                'payload' => $payload ?: null,
                'paid_at' => now(),
            ]);

            $locked->payment_status = PaymentStatus::Paid;

            // Money arriving does not un-ship an order. A shipped or delivered order
            // stays where it is; only one still waiting moves to paid.
            if ($locked->status->canTransitionTo(OrderStatus::Paid)) {
                $locked->status = OrderStatus::Paid;
            }

            $locked->save();

            // Anything the caller holds is now stale.
            $order->refresh();

            return SettlementOutcome::settled($payment);
        });
    }
}
