<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $fillable = ['regions_id', 'name', 'status'];

    public function tripRoutes()
    {
        return $this->hasMany(TripRoute::class);
    }
    public function region()
    {
        return $this->belongsTo(Regions::class, 'regions_id');
    }
}
