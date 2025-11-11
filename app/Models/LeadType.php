<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadType extends Model
{
    protected $table = 'lead_type';

    protected $fillable = [
        'type',
        'status',
        'created_at',
    ];

    public $timestamps = false;
}
