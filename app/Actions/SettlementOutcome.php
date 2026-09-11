<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Payment;

/**
 * What SettlePaymentAction did.
 *
 * "Already settled" is a success, not a failure: a duplicate notification has to
 * be acknowledged rather than rejected, or the gateway keeps redelivering it.
 * Callers that need to distinguish the two — the admin screens, which should not
 * claim to have taken money they did not take — can still ask.
 */
final class SettlementOutcome
{
    private function __construct(
        public readonly bool $settled,
        public readonly ?Payment $payment = null,
    ) {}

    public static function settled(Payment $payment): self
    {
        return new self(true, $payment);
    }

    public static function alreadySettled(): self
    {
        return new self(false);
    }

    /**
     * True when this call is the one that took the money, false when it found the
     * order already paid.
     */
    public function tookEffect(): bool
    {
        return $this->settled;
    }
}
