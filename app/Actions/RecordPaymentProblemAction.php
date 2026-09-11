<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ProblemReason;
use App\Models\Order;
use App\Models\PaymentProblem;
use Illuminate\Support\Facades\Log;

/**
 * Writes the record of a failed payment attempt.
 *
 * Everything that fails a payment routes through here, so the redaction below is
 * the single place that decides what is safe to keep.
 */
class RecordPaymentProblemAction
{
    /**
     * Never persisted or logged. The store password travels in our own outgoing
     * request array, and the signature fields are the gateway's shared secret
     * material — none of it is useful after the fact, and all of it is dangerous
     * sitting in a database an admin screen renders.
     */
    private const REDACTED_KEYS = [
        'store_passwd',
        'store_password',
        'verify_sign',
        'verify_sign_sha2',
        'verify_key',
    ];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(
        ?Order $order,
        ProblemReason $reason,
        string $message,
        ?string $reference = null,
        ?string $valId = null,
        ?float $amount = null,
        ?string $currency = null,
        array $payload = [],
        string $gateway = 'sslcommerz',
    ): PaymentProblem {
        $problem = PaymentProblem::create([
            'order_id' => $order?->id,
            'gateway' => $gateway,
            'gateway_reference' => $reference,
            'val_id' => $valId,
            'reason' => $reason,
            'message' => $message,
            'amount' => $amount,
            'currency' => $currency,
            'payload' => $this->redact($payload),
        ]);

        // Loud enough to notice in logs; the row is the durable record.
        Log::warning('Payment problem recorded', [
            'problem_id' => $problem->id,
            'order' => $order?->order_number,
            'reason' => $reason->value,
            'reference' => $reference,
        ]);

        return $problem;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function redact(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), self::REDACTED_KEYS, true)) {
                unset($payload[$key]);

                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->redact($value);
            }
        }

        return $payload;
    }
}
