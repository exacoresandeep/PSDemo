<?php

namespace App\Models;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dealers extends Model
{
    use HasApiTokens, HasFactory;
    protected $table = 'dealer';
    protected $fillable = [
        'dealer_code',
        'dealer_name',
        'gst_no',
        'pan_no',
        'phone',
        'email',
        'address',
        'password',
        'user_zone',
        'pincode',
        'state',
        'district_id',
        'district',
        'taluk',
        'location',
        'assigned_route_id',
        'password_reset_flag',
        'address_id',
        'product_id',
        
    ];
     protected $casts = [
        'product_id' => 'array',
    ];
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }
     public function addresses()
    {
        return $this->hasMany(DealerAddress::class, 'dealer_id');
    }

    public function primaryAddress()
    {
        return $this->belongsTo(DealerAddress::class, 'address_id');
    }
    public function assignRoute()
    {
        return $this->belongsTo(AssignRoute::class, 'assign_route_id');
    }
    // public function assignRoute()
    // {
    //     return $this->belongsTo(AssignRoute::class, 'assign_route_id');
    // }
  
    public function assignedRoutes()
    {
        return $this->belongsToMany(
            AssignRoute::class,
            'dealer_route_assignments',
            'dealer_id',
            'assign_route_id'
        );
    }

    public function routeAssignments()
    {
        return $this->hasMany(DealerRouteAssignment::class, 'dealer_id', 'id');
    }

}
