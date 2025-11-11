<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoolantLevel extends Model
{
    use HasFactory;

    protected $table = 'coolant_levels';

    protected $fillable = [
        'name',
        'status'
    ];
}
