<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Models\Order;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Exceptions\GatewayNotConfigured;
use App\Payments\PaymentResult;

/**
 * bKash Checkout (tokenised) — scaffolding only.
 *
 * To finish this integration:
 *
 *   1. Put BKASH_APP_KEY / BKASH_APP_SECRET / BKASH_USERNAME / BKASH_PASSWORD in
 *      .env and set PAYMENT_BKASH_ENABLED=true.
 *   2. Grant token (POST /tokenized/checkout/token/grant), cache it for its TTL,
 *      then create the payment and return
 *      PaymentResult::redirect($response['bkashURL'], $response['paymentID']).
 *   3. On callback, call the execute endpoint and only then mark the order paid.
 *      bKash payments are not settled until execute succeeds — a created payment
 *      that is never executed must not be treated as paid.
 */
final class BkashGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'bkash';
    }

    public function label(): string
    {
        return (string) config('payment.gateways.bkash.label', 'bKash');
    }

    public function isConfigured(): bool
    {
        return (bool) config('payment.gateways.bkash.enabled', false)
            && filled(config('payment.gateways.bkash.app_key'))
            && filled(config('payment.gateways.bkash.app_secret'));
    }

    public function charge(Order $order): PaymentResult
    {
        throw GatewayNotConfigured::for($this->key());
    }
}
