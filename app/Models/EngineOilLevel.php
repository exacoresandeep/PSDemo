<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EngineOilLevel extends Model
{
    use HasFactory;

    protected $table = 'engine_oil_levels';

    protected $fillable = [
        'name',
        'status'
    ];
}
