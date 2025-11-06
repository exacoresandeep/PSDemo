<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleGreasingCondition extends Model
{
    use HasFactory;

    protected $table = 'vehicle_greasing_conditions';

    protected $fillable = [
        'name',
        'status'
    ];
}
