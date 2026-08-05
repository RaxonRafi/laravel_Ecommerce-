<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Models\Order;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\PaymentResult;

/**
 * No integration: the courier collects the money. The payment stays pending until
 * an administrator marks the order delivered.
 */
final class CashOnDeliveryGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'cod';
    }

    public function label(): string
    {
        return (string) config('payment.gateways.cod.label', 'Cash on Delivery');
    }

    public function isConfigured(): bool
    {
        return (bool) config('payment.gateways.cod.enabled', true);
    }

    public function charge(Order $order): PaymentResult
    {
        return PaymentResult::pending(payload: [
            'note' => 'Payment to be collected on delivery.',
        ]);
    }
}
