<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brands extends Model
{
    protected $table = 'brands';

    protected $fillable = [
        'name',
        'status',
        'created_at',
    ];

    public $timestamps = false; 
}

