<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    use HasFactory;

    protected $fillable = ['product_id','type_name','rate'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
