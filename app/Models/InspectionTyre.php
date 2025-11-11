<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionTyre extends Model
{

    protected $fillable = ['inspection_id', 'axle_type', 'tyre_type', 'tyre_category', 'tyre_condition'];

    public function inspection()
    {
        return $this->belongsTo(VehicleInspection::class, 'inspection_id');
    }
}
