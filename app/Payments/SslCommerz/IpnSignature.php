<?php

declare(strict_types=1);

namespace App\Payments\SslCommerz;

/**
 * Verifies the hash SSLCommerz signs its IPN with.
 *
 * This runs before any outbound call. It is not proof of payment — a valid
 * signature still gets checked against the validation API — but it stops an
 * unauthenticated public endpoint from being used to make us hammer SSLCommerz
 * with lookups for transactions nobody ever made.
 *
 * The scheme: `verify_key` names the fields that were signed, comma separated.
 * Sort them, join as key=value pairs, append the md5 of the store password, and
 * md5 the lot. The result must equal `verify_sign`.
 */
class IpnSignature
{
    /**
     * @param  array<string, mixed>  $payload  the raw IPN body
     */
    public function verify(array $payload): bool
    {
        $signature = (string) ($payload['verify_sign'] ?? '');
        $keyList = (string) ($payload['verify_key'] ?? '');

        if ($signature === '' || $keyList === '') {
            return false;
        }

        $storePassword = (string) config('payment.gateways.sslcommerz.store_password');

        if ($storePassword === '') {
            return false;
        }

        $keys = array_filter(array_map('trim', explode(',', $keyList)));
        sort($keys);

        $pairs = [];

        foreach ($keys as $key) {
            $pairs[] = $key.'='.($payload[$key] ?? '');
        }

        $pairs[] = 'store_passwd='.md5($storePassword);
        sort($pairs);

        return hash_equals(md5(implode('&', $pairs)), $signature);
    }
}
