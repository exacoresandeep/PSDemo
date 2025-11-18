<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductMeasurement extends Model
{
    use HasFactory;

    protected $table = 'products_measurements';

    protected $fillable = [
        'product_id',
        'attribute_name',
        'status',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
