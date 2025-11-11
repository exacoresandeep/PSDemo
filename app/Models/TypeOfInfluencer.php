<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeOfInfluencer extends Model
{
    protected $table = 'type_of_influencers';

    protected $fillable = [
        'name',
        'status',
        'created_at',
    ];

    public $timestamps = false;
}
