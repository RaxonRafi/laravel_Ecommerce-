<?php

declare(strict_types=1);

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\BkashGateway;
use App\Payments\Gateways\CashOnDeliveryGateway;
use App\Payments\Gateways\SslCommerzGateway;
use InvalidArgumentException;

/**
 * Resolves payment gateways by key. Adding a gateway means registering its class
 * here and adding a config entry — nothing in the order flow changes.
 */
class PaymentGatewayManager
{
    /** @var array<string, class-string<PaymentGateway>> */
    private const GATEWAYS = [
        'cod' => CashOnDeliveryGateway::class,
        'sslcommerz' => SslCommerzGateway::class,
        'bkash' => BkashGateway::class,
    ];

    public function make(string $key): PaymentGateway
    {
        if (! isset(self::GATEWAYS[$key])) {
            throw new InvalidArgumentException("Unknown payment gateway: {$key}");
        }

        return app(self::GATEWAYS[$key]);
    }

    public function default(): PaymentGateway
    {
        return $this->make((string) config('payment.default', 'cod'));
    }

    /**
     * Only gateways that are enabled and hold credentials. This is what checkout
     * offers, so an unconfigured gateway can never be selected.
     *
     * @return array<string, PaymentGateway>
     */
    public function available(): array
    {
        $available = [];

        foreach (array_keys(self::GATEWAYS) as $key) {
            $gateway = $this->make($key);

            if ($gateway->isConfigured()) {
                $available[$key] = $gateway;
            }
        }

        return $available;
    }

    public function isAvailable(string $key): bool
    {
        return array_key_exists($key, $this->available());
    }
}
