<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'trip_code',
        'vehicle_id',
        'driver_id',
        'status',
        'total_quantity',
        'approx_km',
        'from_location',
        'to_location',
        'assign_date',
        'delivery_date',
        'pickup_point_flag',
        'salary_type',
        'start_km',
        'start_km_image',
        'return_start_km',
        'return_start_km_image',
        'return_start_time',
        'garage_km',
        'garage_km_image',
        'reached_garage_time',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'assign_date' => 'date',
        'delivery_date' => 'date',
        'total_quantity' => 'decimal:2',
        'approx_km' => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function orders()
    {
        return $this->hasMany(TripOrder::class);
    }

    public function pickups()
    {
        return $this->hasMany(TripPickup::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(TripStatusHistory::class);
    }
}
