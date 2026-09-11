<?php

declare(strict_types=1);

namespace App\Payments\SslCommerz;

use App\Payments\Exceptions\SslCommerzRequestFailed;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Every outbound call to SSLCommerz goes through here.
 *
 * Only this class knows which host to talk to, so no call site can accidentally
 * send sandbox credentials to the live gateway. Nothing here interprets a
 * payment outcome — it returns the gateway's own words and lets
 * SslCommerzValidator decide what they mean.
 */
class SslCommerzClient
{
    private const SESSION_PATH = '/gwprocess/v4/api.php';

    private const VALIDATION_PATH = '/validator/api/validationserverAPI.php';

    private const TRANSACTION_QUERY_PATH = '/validator/api/merchantTransIDvalidationAPI.php';

    /**
     * Ask the gateway to open a payment session.
     *
     * @param  array<string, mixed>  $payload  everything except the store credentials
     * @return array<string, mixed> the decoded gateway response
     *
     * @throws SslCommerzRequestFailed
     */
    public function createSession(array $payload): array
    {
        $response = $this->post(self::SESSION_PATH, $payload + $this->credentials(), 'session');

        // A refusal arrives as a perfectly healthy 200, so the body is the only
        // place the failure shows up.
        if (($response['status'] ?? null) !== 'SUCCESS' || blank($response['GatewayPageURL'] ?? null)) {
            throw SslCommerzRequestFailed::sessionRejected(
                (string) ($response['failedreason'] ?? $response['status'] ?? 'no reason given'),
            );
        }

        return $response;
    }

    /**
     * Confirm a transaction the gateway told us about, using the validation id it
     * supplied in the callback.
     *
     * @return array<string, mixed>
     *
     * @throws SslCommerzRequestFailed
     */
    public function validateByValId(string $valId): array
    {
        return $this->get(self::VALIDATION_PATH, [
            'val_id' => $valId,
            'format' => 'json',
        ] + $this->credentials(), 'validation');
    }

    /**
     * Ask about a transaction using our own reference. This is the only lookup
     * available when no callback ever arrived, so it is what the admin re-check
     * button relies on.
     *
     * @return array<string, mixed>
     *
     * @throws SslCommerzRequestFailed
     */
    public function queryByTransactionId(string $transactionId): array
    {
        return $this->get(self::TRANSACTION_QUERY_PATH, [
            'tran_id' => $transactionId,
            'format' => 'json',
        ] + $this->credentials(), 'transaction query');
    }

    /**
     * Sandbox and live are separate environments with separate credentials; the
     * `sandbox` flag picks both host and, by extension, which store the
     * credentials belong to.
     */
    public function baseUrl(): string
    {
        $key = config('payment.gateways.sslcommerz.sandbox', true)
            ? 'sandbox_url'
            : 'live_url';

        return rtrim((string) config("payment.gateways.sslcommerz.{$key}"), '/');
    }

    /**
     * @return array{store_id: string, store_passwd: string}
     */
    private function credentials(): array
    {
        return [
            'store_id' => (string) config('payment.gateways.sslcommerz.store_id'),
            'store_passwd' => (string) config('payment.gateways.sslcommerz.store_password'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws SslCommerzRequestFailed
     */
    private function post(string $path, array $payload, string $endpoint): array
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->retry(2, 200, throw: false)
                ->post($this->baseUrl().$path, $payload);
        } catch (ConnectionException $e) {
            throw SslCommerzRequestFailed::unreachable($endpoint, $e);
        }

        if ($response->failed()) {
            throw SslCommerzRequestFailed::badStatus($endpoint, $response->status());
        }

        return (array) $response->json();
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     *
     * @throws SslCommerzRequestFailed
     */
    private function get(string $path, array $query, string $endpoint): array
    {
        try {
            $response = Http::timeout(30)
                ->retry(2, 200, throw: false)
                ->get($this->baseUrl().$path, $query);
        } catch (ConnectionException $e) {
            throw SslCommerzRequestFailed::unreachable($endpoint, $e);
        }

        if ($response->failed()) {
            throw SslCommerzRequestFailed::badStatus($endpoint, $response->status());
        }

        return (array) $response->json();
    }
}
