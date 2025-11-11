<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'assistance_id',
        'vehicle_id',
        'maintenance_type',
        'service_category',
        'service_type',
        'remarks',
        'employee_name',
        'service_center_name',
        'mechanic_name',
        'phone_number',
        'status'
    ];

    public function assistance()
    {
        return $this->belongsTo(Assistance::class);
    }

    public function images()
    {
        return $this->hasMany(MaintenanceImage::class, 'maintenance_id');
    }
    public function jobCard()
    {
        return $this->hasOne(JobCard::class);
    }
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
}
