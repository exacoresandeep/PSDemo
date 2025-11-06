<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id', 'inspection_type', 'inspection_date','inspection_km',
        'engine_oil_level','coolant_level','clutch_fluid','vehicle_greasing','vehicle_washing',
        'mirror_condition','indicator_condition','battery_condition','mudflap_condition',
        'essential_equipment','remarks', 'notification_status', 'status'
    ];

    protected $casts = [
        'essential_equipment' => 'array',
        'inspection_date' => 'date',
    ];

    public function vehicle() {
        return $this->belongsTo(Vehicle::class);
    }

    public function tyres() {
        return $this->hasMany(InspectionTyre::class, 'inspection_id');
    }
    public function photos() {
        return $this->hasMany(InspectionPhoto::class, 'inspection_id');
    }
    public function maintenance()
    {
        return $this->hasOne(VehicleServiceMaintenance::class, 'inspection_id');
    }
    public function vehicleType() {
        return $this->belongsTo(VehicleType::class);
    }
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}