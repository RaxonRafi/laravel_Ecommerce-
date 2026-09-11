<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_current_price',
        'color_id',
        'size_id',
        'cart_amount',
        'user_id',
    ];

    public function relationtoproduct()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    public function relationtocolor()
    {
        return $this->hasOne(Color::class, 'id', 'color_id');
    }

    public function relationtosize()
    {
        return $this->hasOne(Size::class, 'id', 'size_id');
    }
}
