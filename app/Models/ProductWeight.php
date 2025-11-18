<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductWeight extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_type_id',
        'product_measurement_id',
        'weight',
        'status',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function measurement()
    {
        return $this->belongsTo(ProductMeasurement::class, 'product_measurement_id');
    }
}
