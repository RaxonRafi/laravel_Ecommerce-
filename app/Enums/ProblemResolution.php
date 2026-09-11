<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How a problem payment was settled in the end.
 *
 * Since a manually settled order carries the same `paid` payment status as a
 * gateway-settled one, this is where the distinction survives for reconciliation.
 */
enum ProblemResolution: string
{
    /** The gateway was asked again and confirmed the transaction after all. */
    case GatewayRecheck = 'gateway_recheck';

    /** An administrator confirmed money arrived outside the gateway. */
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::GatewayRecheck => 'Confirmed by gateway',
            self::Manual => 'Recorded manually',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::GatewayRecheck => 'success',
            self::Manual => 'info',
        };
    }
}
