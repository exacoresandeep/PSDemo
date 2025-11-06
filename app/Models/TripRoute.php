<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripRoute extends Model
{


    use HasFactory;
    protected $table = 'routes';
    protected $fillable = ['district_id', 'locations'];
  
    protected $casts = [
        'locations' => 'array', 
    ];

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }


    public function assignedRoutes()
    {
        return $this->hasMany(AssignRoute::class);
    }
}
