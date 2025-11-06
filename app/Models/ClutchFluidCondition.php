<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClutchFluidCondition extends Model
{
    use HasFactory;

    protected $table = 'clutch_fluid_conditions';

    protected $fillable = [
        'name',
        'status'
    ];
}
