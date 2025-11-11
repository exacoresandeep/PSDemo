<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MudFlapCondition extends Model
{
    use HasFactory;

    protected $table = 'mud_flap_conditions';

    protected $fillable = [
        'name',
        'status'
    ];
}
