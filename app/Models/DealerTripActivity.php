<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealerTripActivity extends Model
{
    use HasFactory;

    protected $table = 'dealer_trip_activity';

    protected $fillable = [
        'assign_route_id',
        'dealer_id',
        'record_details',  
        'attachments',    
        'activity_status',
        'completed_date', 
    ];

    protected $casts = [
        'attachments' => 'array', 
    ];

    public function assignRoute()
    {
        return $this->belongsTo(AssignRoute::class, 'assign_route_id');
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }
}
