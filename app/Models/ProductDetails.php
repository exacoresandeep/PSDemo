<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ProductDetails extends Model
{
    use HasFactory;
    protected $table = 'products_details'; 
    protected $fillable = ['product_id', 'type_id', 'product_name', 'item_profile', 'item_thickness', 'primary_group', 'total_available_quantity', 'rate', 'availability_status', 'stock_updated_at'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class, 'type_id');
    }
    public function getStockUpdatedAtFormattedAttribute()
    {
        return $this->stock_updated_at 
            ? Carbon::parse($this->stock_updated_at)->format('d/m/Y h:i A') 
            : null;
    }

}