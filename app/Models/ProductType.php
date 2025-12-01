<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    use HasFactory;
     protected $casts = [
        'rate' => 'double',
    ];

    protected $fillable = ['product_id','type_name','rate'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function productDetails()
    {
        return $this->hasMany(ProductDetails::class, 'type_id');
    }

}
