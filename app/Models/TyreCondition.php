<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TyreCondition extends Model
{
    use HasFactory;

    protected $table = 'tyre_conditions';

    protected $fillable = [
        'name',
        'status'
    ];
}
