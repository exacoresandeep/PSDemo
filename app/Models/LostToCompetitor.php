<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LostToCompetitor extends Model
{
    protected $table = 'lost_to_competitor';

    protected $fillable = [
        'name',
        'status',
        'created_at',
    ];

    public $timestamps = false; 
}
