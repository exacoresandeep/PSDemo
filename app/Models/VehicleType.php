<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleType extends Model
{
    use HasFactory;


    protected $table = 'vehicle_type';

  
    protected $fillable = [
        'vehicle_type_name',
        'vehicle_category_id',
        'status',
    ];

   
    public function category()
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }
}
