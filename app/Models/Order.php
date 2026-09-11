<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'subtotal',
        'discount_total',
        'shipping_total',
        'grand_total',
        'currency',
        'payment_method',
        'payment_status',
        'coupon_id',
        'coupon_code',
        'shipping_name',
        'shipping_phone',
        'shipping_address',
        'shipping_country_id',
        'shipping_city',
        'notes',
        'placed_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'payment_method' => PaymentMethod::class,
        'payment_status' => PaymentStatus::class,
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'placed_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentProblems(): HasMany
    {
        return $this->hasMany(PaymentProblem::class);
    }

    public function shippingCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'shipping_country_id');
    }

    /**
     * Sequential, human-quotable reference such as ORD-2026-000042.
     *
     * Called inside the order-placement transaction, where the orders table is
     * already locked, so the read-then-increment cannot race.
     */
    public static function generateOrderNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "ORD-{$year}-";

        $last = static::where('order_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('order_number');

        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    /**
     * Puts stock back for every line on this order. Used when an order is cancelled.
     */
    public function restockItems(): void
    {
        foreach ($this->items as $item) {
            DB::table('inventories')
                ->where('product_id', $item->product_id)
                ->where('color_id', $item->color_id)
                ->where('size_id', $item->size_id)
                ->increment('quantity', $item->quantity);
        }
    }
}
