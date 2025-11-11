<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealerRouteAssignment extends Model
{
    use HasFactory;

    protected $table = 'dealer_route_assignments';

    protected $fillable = [
        'assign_route_id',
        'dealer_id',
        'location',
    ];

    public function assignedRoute()
    {
        return $this->belongsTo(AssignRoute::class, 'assign_route_id');
    }
    public function dealer()
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }
}
