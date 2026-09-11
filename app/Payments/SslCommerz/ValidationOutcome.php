<?php

declare(strict_types=1);

namespace App\Payments\SslCommerz;

use App\Enums\ProblemReason;
use App\Models\Order;

/**
 * What the validator concluded about a transaction.
 *
 * Either it is safe to settle the order for `amount`, or it is not and the
 * reason is one the problem payments table understands.
 */
final class ValidationOutcome
{
    /**
     * @param  array<string, mixed>  $payload
     */
    private function __construct(
        public readonly bool $valid,
        public readonly ?Order $order = null,
        public readonly ?float $amount = null,
        public readonly ?string $currency = null,
        public readonly ?ProblemReason $reason = null,
        public readonly string $message = '',
        public readonly array $payload = [],
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function valid(Order $order, float $amount, string $currency, array $payload = []): self
    {
        return new self(true, $order, $amount, $currency, payload: $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function rejected(
        ProblemReason $reason,
        string $message,
        ?Order $order = null,
        ?float $amount = null,
        ?string $currency = null,
        array $payload = [],
    ): self {
        return new self(false, $order, $amount, $currency, $reason, $message, $payload);
    }
}
