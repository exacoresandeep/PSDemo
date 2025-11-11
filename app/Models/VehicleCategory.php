<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class VehicleCategory extends Model
{

    use HasFactory;

    protected $table = 'vehicle_category';

    protected $fillable = [
        'vehicle_category_name',
        'status',
    ];

    protected $casts = [
        'status' => 'integer', // Enum status can be treated as integer
    ];

    public function vehicleTypes()
    {
        return $this->hasMany(VehicleType::class, 'vehicle_category_id');
    }
}

