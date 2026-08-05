<?php

declare(strict_types=1);

namespace App\Payments\Contracts;

use App\Models\Order;
use App\Payments\PaymentResult;

interface PaymentGateway
{
    /**
     * Config key identifying this gateway, e.g. "cod" or "sslcommerz".
     */
    public function key(): string;

    /**
     * Human-readable name shown at checkout.
     */
    public function label(): string;

    /**
     * Whether this gateway has everything it needs to take a payment. A gateway
     * missing credentials is never offered to a customer.
     */
    public function isConfigured(): bool;

    /**
     * Begin taking payment for the order.
     */
    public function charge(Order $order): PaymentResult;
}
