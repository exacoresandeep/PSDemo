<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatteryCondition extends Model
{
    use HasFactory;

    protected $table = 'battery_conditions';

    protected $fillable = [
        'name',
        'status'
    ];
}
