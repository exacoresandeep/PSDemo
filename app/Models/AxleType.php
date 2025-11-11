<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AxleType extends Model
{
    use HasFactory;

    protected $table = 'axle_types';

    protected $fillable = [
        'vehicle_type_id',
        'name',
        'status',
    ];

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id');
    }

    public function tyreTypes()
    {
        return $this->hasMany(TyreType::class, 'axle_type_id');
    }
}
