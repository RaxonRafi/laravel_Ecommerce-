<?php

declare(strict_types=1);

namespace App\Payments\Exceptions;

use RuntimeException;

class GatewayNotConfigured extends RuntimeException
{
    public static function for(string $gateway): self
    {
        return new self(
            "The '{$gateway}' payment gateway is not configured. Add its credentials "
            ."to .env, enable it in config/payment.php, and implement its charge() method."
        );
    }
}
