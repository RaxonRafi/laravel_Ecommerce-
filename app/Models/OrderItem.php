<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'color_id',
        'size_id',
        'product_name',
        'sku',
        'color_name',
        'size_name',
        'unit_price',
        'quantity',
        'line_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The live product, which may since have been renamed or repriced. Display
     * should prefer this row's own snapshot columns.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
