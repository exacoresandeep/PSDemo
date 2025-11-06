<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TripPickup extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'trip_id',
        'pickup_date',
        'pickup_point',
        'address',
        'office_phone',
        'contact_person_name',
        'contact_person_phone',
        'attachment',
        'start_km',       
        'start_km_image',
        'start_time',
        'end_km',
        'end_km_image',
        'end_time'
    ];

    protected $casts = [
        'pickup_date' => 'date',
        'attachment' => 'array',
        'start_time'  => 'datetime',
        'end_time'    => 'datetime',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
