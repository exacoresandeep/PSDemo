<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TripOrder extends Model
{
    use HasFactory;
    public $timestamps = false; 
    protected $fillable = [
        'trip_id',
        'order_id',
        'delivery_point_no',
        'delivery_address',
        'contact_person',
        'contact_phone',
        'office_phone',
        'delivery_date',
        'quantity',
        'current_km',
        'km_image',
        'start_time',
        'end_time',
        'start_km',       
        'start_km_image'
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'quantity' => 'decimal:2',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function dealer()
    {
        return $this->hasOneThrough(
            Dealer::class,
            Order::class,
            'id',       
            'id',       
            'order_id',  
            'dealer_id' 
        );
    }

    public function dealerAddresses()
    {
        return $this->hasManyThrough(
            DealerAddress::class,
            Order::class,
            'id',       
            'dealer_id',  
            'order_id',  
            'dealer_id' 
        );
    }
}
