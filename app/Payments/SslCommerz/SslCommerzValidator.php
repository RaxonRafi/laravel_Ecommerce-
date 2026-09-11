<?php

declare(strict_types=1);

namespace App\Payments\SslCommerz;

use App\Enums\ProblemReason;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Exceptions\SslCommerzRequestFailed;

/**
 * Decides whether a transaction really was paid, for the right order, in the
 * right money.
 *
 * Both the IPN and the admin re-check button come through here, which is what
 * makes the button trustworthy: it is not a second opinion, it is the same
 * opinion asked again.
 *
 * Nothing in the callback body is believed. The amount and currency that matter
 * are the ones the gateway reports over our own outbound connection, checked
 * against the order we stored.
 */
class SslCommerzValidator
{
    /** Statuses SSLCommerz uses for "this money is real". */
    private const SETTLED_STATUSES = ['VALID', 'VALIDATED'];

    public function __construct(private readonly SslCommerzClient $client) {}

    /**
     * @param  array<string, mixed>  $context  the raw callback, kept for the problem record
     *
     * @throws SslCommerzRequestFailed  when the gateway cannot be reached
     */
    public function validate(string $transactionId, ?string $valId = null, array $context = []): ValidationOutcome
    {
        $order = $this->resolveOrder($transactionId);

        if (! $order) {
            return ValidationOutcome::rejected(
                ProblemReason::ValidationMismatch,
                "No order matches transaction reference {$transactionId}.",
                payload: $context,
            );
        }

        // val_id when the gateway handed us one; otherwise ask by our own
        // reference, which is all the admin re-check path has.
        $response = filled($valId)
            ? $this->client->validateByValId((string) $valId)
            : $this->unwrapQuery($this->client->queryByTransactionId($transactionId));

        $status = strtoupper((string) ($response['status'] ?? ''));

        if (! in_array($status, self::SETTLED_STATUSES, true)) {
            return ValidationOutcome::rejected(
                $status === 'FAILED' ? ProblemReason::Declined : ProblemReason::ValidationMismatch,
                $status === ''
                    ? 'The gateway returned no status for this transaction.'
                    : "The gateway reports this transaction as {$status}.",
                order: $order,
                payload: $context + ['validation_response' => $response],
            );
        }

        $currency = strtoupper((string) ($response['currency'] ?? ''));

        if ($currency !== strtoupper((string) $order->currency)) {
            return ValidationOutcome::rejected(
                ProblemReason::ValidationMismatch,
                "Currency mismatch: the order is in {$order->currency} but the gateway settled in {$currency}.",
                order: $order,
                currency: $currency,
                payload: $context + ['validation_response' => $response],
            );
        }

        $paid = (float) ($response['amount'] ?? 0);

        // Compared in paisa. Floats do not compare cleanly, and "close enough" is
        // not a thing to say about money.
        if ($this->toMinorUnits($paid) < $this->toMinorUnits((float) $order->grand_total)) {
            return ValidationOutcome::rejected(
                ProblemReason::ValidationMismatch,
                sprintf(
                    'Amount mismatch: the order is for %s %s but the gateway settled %s %s.',
                    number_format((float) $order->grand_total, 2, '.', ''),
                    $order->currency,
                    number_format($paid, 2, '.', ''),
                    $currency,
                ),
                order: $order,
                amount: $paid,
                currency: $currency,
                payload: $context + ['validation_response' => $response],
            );
        }

        return ValidationOutcome::valid($order, $paid, $currency, $context + ['validation_response' => $response]);
    }

    /**
     * Looks the order up by the reference we stored when the attempt started.
     * Deliberately not parsed out of the transaction id — that string arrives from
     * outside and is not evidence of anything.
     */
    private function resolveOrder(string $transactionId): ?Order
    {
        $payment = Payment::where('gateway_reference', $transactionId)->latest('id')->first();

        return $payment?->order;
    }

    /**
     * The transaction-query endpoint wraps results in `element`; the validation
     * endpoint returns them flat. Normalise to the flat shape.
     *
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function unwrapQuery(array $response): array
    {
        $elements = $response['element'] ?? null;

        if (is_array($elements) && $elements !== []) {
            // Most recent attempt for this reference.
            return (array) $elements[0];
        }

        return $response;
    }

    private function toMinorUnits(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
