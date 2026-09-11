<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Why a payment attempt ended up needing a human.
 *
 * Customer cancellation is deliberately absent: abandoning the payment page is a
 * normal thing to do, not a problem to work through.
 */
enum ProblemReason: string
{
    /** The gateway reached a decision and it was "no". */
    case Declined = 'declined';

    /**
     * A callback arrived that we could not trust: unverifiable signature, wrong
     * amount or currency, a transaction the gateway does not recognise, or a
     * reference matching no order. The category worth watching — it is the one
     * that catches tampering.
     */
    case ValidationMismatch = 'validation_mismatch';

    /** We never got as far as a decision: the session request errored or timed out. */
    case InitiationError = 'initiation_error';

    public function label(): string
    {
        return match ($this) {
            self::Declined => 'Declined by gateway',
            self::ValidationMismatch => 'Validation mismatch',
            self::InitiationError => 'Could not start payment',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Declined => 'danger',
            self::ValidationMismatch => 'warning',
            self::InitiationError => 'secondary',
        };
    }
}
