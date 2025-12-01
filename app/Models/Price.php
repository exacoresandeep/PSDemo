<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    use HasFactory;
    protected $table = 'product_price'; 

    protected $fillable = [
        'product_id',
        'product_type_id',
        'start_date',
        'end_date',
        'dealer_price',
        'advance_dealer_price',
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
    
}

