<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfluencerVisitFollowUp extends Model
{
    use HasFactory;
     protected $table = 'influencer_visit_follow_ups';

    protected $fillable = [
        'influencer_visit_id',
        'follow_up_date',
        'reason',
        'created_by',
        'notification_status',
    ];
    protected $casts = [
        'follow_up_date' => 'datetime', 
    ];
    public function influencervisit()
    {
        return $this->belongsTo(InfluencerVisit::class);
    }
}


