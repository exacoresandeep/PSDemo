<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = ['district_id', 'warehouse_name', 'latitude', 'longitude'];

    public function productStocks()
    {
        return $this->hasMany(ProductStock::class, 'warehouse_id');
    }
}
