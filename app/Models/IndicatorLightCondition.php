<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicatorLightCondition extends Model
{
    use HasFactory;

    protected $table = 'indicator_light_conditions';

    protected $fillable = [
        'name',
        'status'
    ];
}
