<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignRoute extends Model
{
    use HasFactory;
    protected $table = 'assigned_routes';
    public $timestamps = false;
    protected $fillable = ['district_id', 'employee_type_id', 'parent_id', 'employee_id', 'route_name', 'locations', 'notification_status'];

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }
    // public function dealers()
    // {
    //     return $this->hasMany(Dealer::class, 'assigned_route_id', 'id');
    // }
    public function dealers()
    {
        return $this->belongsToMany(
            Dealer::class,
            'dealer_route_assignments', // pivot table
            'assign_route_id',          // foreign key on pivot table
            'dealer_id'                 // related key on pivot table
        );
    }
    public function leads()
    {
        return $this->hasMany(Lead::class, 'assigned_route_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
    // public function assignRoute()
    // {
    //     return $this->belongsTo(AssignRoute::class, 'assigned_route_id');
    // }
    public function dealerAssignments()
    {
        return $this->hasMany(DealerRouteAssignment::class, 'assign_route_id', 'id');
    }
}
