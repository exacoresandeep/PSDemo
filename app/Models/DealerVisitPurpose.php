<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealerVisitPurpose extends Model
{
    protected $table = 'dealer_visit_purpose';

    protected $fillable = [
        'purpose',
        'status',
        'created_at',
    ];

    public $timestamps = false; 
}