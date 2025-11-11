<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WashingCondition extends Model
{
    use HasFactory;

    protected $table = 'washing_conditions';

    protected $fillable = [
        'name',
        'status'
    ];
}
