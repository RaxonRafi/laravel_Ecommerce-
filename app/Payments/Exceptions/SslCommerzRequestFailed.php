<?php

declare(strict_types=1);

namespace App\Payments\Exceptions;

use RuntimeException;
use Throwable;

/**
 * The gateway could not be reached, or answered with something we cannot act on.
 *
 * This is never "the customer's payment failed" — that is a decision the gateway
 * made and reported successfully. This is "we never got an answer", which is why
 * callers turn it into an `initiation_error` problem payment rather than telling
 * the customer their card was declined.
 */
class SslCommerzRequestFailed extends RuntimeException
{
    public static function unreachable(string $endpoint, Throwable $previous): self
    {
        return new self(
            "Could not reach the SSLCommerz {$endpoint} endpoint: {$previous->getMessage()}",
            0,
            $previous,
        );
    }

    public static function badStatus(string $endpoint, int $status): self
    {
        return new self("The SSLCommerz {$endpoint} endpoint returned HTTP {$status}.");
    }

    /**
     * A session request that came back 200 but carried no payment page — usually a
     * rejected store id, a duplicate transaction id, or a malformed amount. The
     * gateway explains itself in `failedreason`.
     */
    public static function sessionRejected(string $reason): self
    {
        return new self("SSLCommerz refused to create a payment session: {$reason}");
    }
}
