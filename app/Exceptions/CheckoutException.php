<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Conditions that legitimately stop an order being placed. These are shown to the
 * customer, so the messages are user-facing rather than diagnostic.
 */
class CheckoutException extends RuntimeException
{
    public static function emptyCart(): self
    {
        return new self('Your cart is empty.');
    }

    public static function noShippingDestination(): self
    {
        return new self('Please choose your delivery country and city before checking out.');
    }

    public static function insufficientStock(string $productName, int $available): self
    {
        return new self($available > 0
            ? "Only {$available} left of \"{$productName}\". Please reduce the quantity."
            : "\"{$productName}\" has just gone out of stock.");
    }

    public static function productUnavailable(string $message): self
    {
        return new self($message);
    }

    public static function gatewayUnavailable(): self
    {
        return new self('That payment method is not available. Please choose another.');
    }
}
