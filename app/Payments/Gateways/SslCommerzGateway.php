<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Exceptions\GatewayNotConfigured;
use App\Payments\Exceptions\SslCommerzRequestFailed;
use App\Payments\PaymentResult;
use App\Payments\SslCommerz\SslCommerzClient;
use Illuminate\Support\Str;

/**
 * SSLCommerz hosted checkout.
 *
 * The customer is handed to a page SSLCommerz hosts, pays there, and is sent
 * back. The callback — not the browser redirect — is what settles the order: a
 * customer can close the tab before returning, and the return URL can be forged.
 * PaymentCallbackController re-validates every callback server-side before an
 * order is marked paid.
 *
 * Sandbox and live are separate environments with separate credentials;
 * SslCommerzClient picks the host, so nothing here names one.
 */
final class SslCommerzGateway implements PaymentGateway
{
    public function __construct(private readonly SslCommerzClient $client) {}

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

    /**
     * @throws GatewayNotConfigured  when credentials are missing
     * @throws SslCommerzRequestFailed  when the gateway cannot be reached or refuses
     */
    public function charge(Order $order): PaymentResult
    {
        if (! $this->isConfigured()) {
            throw GatewayNotConfigured::for($this->key());
        }

        $transactionId = $this->startAttempt($order);

        $response = $this->client->createSession($this->sessionPayload($order, $transactionId));

        return PaymentResult::redirect(
            (string) $response['GatewayPageURL'],
            $transactionId,
            ['sessionkey' => $response['sessionkey'] ?? null],
        );
    }

    /**
     * Stamps this attempt's reference onto a pending payment row so a returning
     * callback can find its way back to the order.
     *
     * PlaceOrderAction has already written one pending row for the order; a
     * customer retrying gets a fresh row, which is what keeps `payments` an
     * attempt-by-attempt audit trail.
     */
    private function startAttempt(Order $order): string
    {
        $transactionId = $this->generateTransactionId($order);

        $pending = $order->payments()
            ->where('status', PaymentStatus::Pending)
            ->whereNull('gateway_reference')
            ->latest('id')
            ->first();

        if ($pending) {
            $pending->update([
                'gateway' => $this->key(),
                'gateway_reference' => $transactionId,
            ]);

            return $transactionId;
        }

        Payment::create([
            'order_id' => $order->id,
            'gateway' => $this->key(),
            'gateway_reference' => $transactionId,
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'status' => PaymentStatus::Pending,
        ]);

        return $transactionId;
    }

    /**
     * Unique per attempt and traceable to its order by eye. SSLCommerz caps
     * tran_id at 30 characters, which "ORD-2026-000042-K3F9QZ" sits inside.
     *
     * The order is never recovered by parsing this string — it is looked up by
     * the stored reference, because the value comes back to us from outside.
     */
    private function generateTransactionId(Order $order): string
    {
        return $order->order_number.'-'.Str::upper(Str::random(6));
    }

    /**
     * Everything the gateway is told about the charge comes from the stored
     * order, never from the request that triggered the payment.
     *
     * @return array<string, mixed>
     */
    private function sessionPayload(Order $order, string $transactionId): array
    {
        $order->loadMissing('user');

        return [
            'total_amount' => number_format((float) $order->grand_total, 2, '.', ''),
            'currency' => $order->currency,
            'tran_id' => $transactionId,

            'success_url' => route('payment.sslcommerz.success'),
            'fail_url' => route('payment.sslcommerz.fail'),
            'cancel_url' => route('payment.sslcommerz.cancel'),
            'ipn_url' => route('payment.sslcommerz.ipn'),

            'cus_name' => $order->shipping_name,
            'cus_email' => $order->user?->email ?? 'noreply@'.parse_url((string) config('app.url'), PHP_URL_HOST),
            'cus_phone' => $order->shipping_phone,
            'cus_add1' => $order->shipping_address,
            'cus_city' => $order->shipping_city,
            'cus_country' => $order->shippingCountry?->name ?? 'Bangladesh',

            'shipping_method' => 'Courier',
            'num_of_item' => $order->items()->count(),
            'ship_name' => $order->shipping_name,
            'ship_add1' => $order->shipping_address,
            'ship_city' => $order->shipping_city,
            'ship_country' => $order->shippingCountry?->name ?? 'Bangladesh',
            'ship_postcode' => '0000',

            'product_name' => 'Order '.$order->order_number,
            'product_category' => 'General',
            'product_profile' => 'physical-goods',

            'value_a' => $order->order_number,
        ];
    }
}
