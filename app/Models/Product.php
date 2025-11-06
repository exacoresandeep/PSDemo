<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['product_name'];

    public function productTypes()
    {
        return $this->hasMany(ProductType::class, 'product_id');
    }
    
}
