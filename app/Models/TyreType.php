<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TyreType extends Model
{
    use HasFactory;

    protected $table = 'tyre_types';

    protected $fillable = [
        'vehicle_type_id',
        'axle_type_id',
        'name',
        'status'
    ];

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id');
    }

    public function axleType()
    {
        return $this->belongsTo(AxleType::class, 'axle_type_id');
    }
}
