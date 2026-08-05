<?php

declare(strict_types=1);

namespace App\Payments;

use App\Enums\PaymentStatus;

/**
 * Outcome of asking a gateway to take payment for an order.
 *
 * `redirectUrl` is set only for gateways that hand the customer off to a hosted
 * payment page; cash on delivery returns null and the order completes inline.
 */
final class PaymentResult
{
    public function __construct(
        public readonly PaymentStatus $status,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $reference = null,
        public readonly array $payload = [],
    ) {}

    public static function pending(?string $reference = null, array $payload = []): self
    {
        return new self(PaymentStatus::Pending, null, $reference, $payload);
    }

    public static function redirect(string $url, ?string $reference = null, array $payload = []): self
    {
        return new self(PaymentStatus::Pending, $url, $reference, $payload);
    }

    public static function paid(?string $reference = null, array $payload = []): self
    {
        return new self(PaymentStatus::Paid, null, $reference, $payload);
    }

    public function requiresRedirect(): bool
    {
        return $this->redirectUrl !== null;
    }
}
