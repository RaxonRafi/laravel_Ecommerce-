<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Bootstrap contextual class used for the status badge.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'secondary',
            self::Paid => 'info',
            self::Processing => 'primary',
            self::Shipped => 'warning',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
        };
    }

    /**
     * Statuses this one may legally move to. Prevents nonsense transitions such as
     * jumping straight from pending to delivered, or reviving a cancelled order.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Paid, self::Processing, self::Cancelled],
            self::Paid => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * Cancelling returns reserved stock to inventory; the later states have already
     * shipped goods, so stock must not be restored.
     */
    public function restocksOnCancel(): bool
    {
        return in_array($this, [self::Pending, self::Paid, self::Processing], true);
    }
}
