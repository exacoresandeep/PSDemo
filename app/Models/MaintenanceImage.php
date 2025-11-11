<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceImage extends Model
{
    use HasFactory;

    protected $fillable = ['maintenance_id', 'image'];

    public function maintenance()
    {
        return $this->belongsTo(MaintenanceReport::class, 'maintenance_id');
    }
}
