<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleServiceMaintenance extends Model
{
    protected $table = 'vehicle_service_maintenances';
    
    protected $fillable = [
        'inspection_id',
        'supervisor_name',
        'mechanic_name',
        'service_date',
        'service_kilometer',
        'service_remarks',
    ];

    public function inspection()
    {
        return $this->belongsTo(VehicleInspection::class, 'inspection_id');
    }
}
