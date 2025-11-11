<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RescheduledRoute extends Model
{

    use HasFactory;

    protected $table = 'rescheduled_routes';

    protected $fillable = [
        'employee_id',
        'day',
        'assign_date',
        'assigned_route_id',
        'route_name',
        'locations',
        'customers', 
        'notification_status', 
    ];

    // protected $casts = [
    //     'assign_date' => 'date', 
    //     'locations' => 'array', 
    //     'customers' => 'array', 
    // ];

   
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignedRoute()
    {
        return $this->belongsTo(AssignRoute::class, 'assigned_route_id');
    }
}

