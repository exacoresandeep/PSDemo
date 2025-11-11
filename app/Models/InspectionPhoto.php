<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionPhoto extends Model {
    
    protected $fillable = ['inspection_id','photo_path'];

    public function inspection() { 
        
        return $this->belongsTo(VehicleInspection::class, 'inspection_id'); 
    }
}
