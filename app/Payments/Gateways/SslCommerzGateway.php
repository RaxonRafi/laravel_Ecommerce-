<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Models\Order;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Exceptions\GatewayNotConfigured;
use App\Payments\PaymentResult;

/**
 * SSLCommerz — scaffolding only.
 *
 * To finish this integration:
 *
 *   1. Put SSLCOMMERZ_STORE_ID / SSLCOMMERZ_STORE_PASSWORD in .env and set
 *      PAYMENT_SSLCOMMERZ_ENABLED=true.
 *   2. In charge(), POST the session request to the SSLCommerz gateway
 *      (sandbox: securepay.sslcommerz.com/gwprocess/v4/api.php) and return
 *      PaymentResult::redirect($response['GatewayPageURL']).
 *   3. Implement the IPN/success callback in PaymentCallbackController, then
 *      validate the transaction server-side before marking the order paid.
 *
 * The callback, not the browser redirect, must be treated as proof of payment —
 * a customer can close the tab before returning, and the redirect URL can be
 * forged.
 */
final class SslCommerzGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'sslcommerz';
    }

    public function label(): string
    {
        return (string) config('payment.gateways.sslcommerz.label', 'SSLCommerz');
    }

    public function isConfigured(): bool
    {
        return (bool) config('payment.gateways.sslcommerz.enabled', false)
            && filled(config('payment.gateways.sslcommerz.store_id'))
            && filled(config('payment.gateways.sslcommerz.store_password'));
    }

    public function charge(Order $order): PaymentResult
    {
        throw GatewayNotConfigured::for($this->key());
    }
}
