<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MirrorCondition extends Model
{
    use HasFactory;

    protected $table = 'mirror_conditions';

    protected $fillable = [
        'name',
        'status'
    ];
}
