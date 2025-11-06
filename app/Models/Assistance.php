<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assistance extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'assistance_type_id',
        'remarks',
        'image',
        'support_date',
        'expiry_date',
        'lat',
        'lon',
        'close_date',
        'status',
        'maintenance_type',
        'employee_id',
        'employee_phone'
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function type()
    {
        return $this->belongsTo(AssistanceType::class, 'assistance_type_id');
    }

    public function maintenanceReports()
    {
        return $this->hasMany(MaintenanceReport::class);
    }
    public function images()
    {
        return $this->hasMany(AssistanceImage::class);
    }
}
