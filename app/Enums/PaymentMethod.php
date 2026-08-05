<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case SslCommerz = 'sslcommerz';
    case Bkash = 'bkash';

    public function label(): string
    {
        return match ($this) {
            self::Cod => 'Cash on Delivery',
            self::SslCommerz => 'SSLCommerz',
            self::Bkash => 'bKash',
        };
    }

    /**
     * Whether the customer is sent to an external gateway to pay. Cash on delivery
     * is settled on handover, so the order is placed without a redirect.
     */
    public function requiresRedirect(): bool
    {
        return $this !== self::Cod;
    }
}
