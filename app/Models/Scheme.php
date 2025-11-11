<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scheme extends Model
{
    protected $table = 'scheme';

    protected $fillable = [
        'scheme',
        'status',
    ];

    public $timestamps = false; 
}