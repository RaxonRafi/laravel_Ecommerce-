<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProblemReason;
use App\Enums\ProblemResolution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A payment attempt that failed and may still owe the shop money.
 *
 * `order_id` can be null: a callback whose transaction reference matches no order
 * is recorded here with nothing to attach it to.
 */
class PaymentProblem extends Model
{
    use HasFactory;

    /** Named by the shop, not by Laravel's pluraliser. */
    protected $table = 'payments_problem';

    protected $fillable = [
        'order_id',
        'gateway',
        'gateway_reference',
        'val_id',
        'reason',
        'message',
        'amount',
        'currency',
        'payload',
        'resolution',
        'resolved_by',
        'resolved_at',
        'resolution_reference',
        'resolution_note',
    ];

    protected $casts = [
        'reason' => ProblemReason::class,
        'resolution' => ProblemResolution::class,
        'amount' => 'decimal:2',
        'payload' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * Re-checking asks the gateway about a transaction it has already seen, so a
     * row that never reached the gateway has nothing to ask about.
     */
    public function canBeRecheckedWithGateway(): bool
    {
        return ! $this->isResolved() && filled($this->gateway_reference);
    }

    /** @param  Builder<PaymentProblem>  $query */
    public function scopeUnresolved(Builder $query): void
    {
        $query->whereNull('resolved_at');
    }

    /** @param  Builder<PaymentProblem>  $query */
    public function scopeResolved(Builder $query): void
    {
        $query->whereNotNull('resolved_at');
    }
}
