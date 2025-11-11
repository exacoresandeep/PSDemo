<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfluencerVisitPurpose extends Model
{
    protected $table = 'influencer_visit_purpose';

    protected $fillable = [
        'purpose',
        'status',
        'created_at',
    ];

    public $timestamps = false;
}
