<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    use HasFactory;
    protected $table = 'products_stock'; 
   protected $fillable = [
        'product_details_id',
        'warehouse_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];
    public function productDetails()
    {
        return $this->belongsTo(ProductDetails::class, 'product_details_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
 
}
