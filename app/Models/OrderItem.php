<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'product_id', 'total_quantity', 'balance_quantity', 'product_details'
    ];

    protected $casts = [
        'product_details' => 'array',
        'total_quantity' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function productDetails()
    {
        return $this->hasMany(ProductDetails::class, 'order_item_id');
    }

}
