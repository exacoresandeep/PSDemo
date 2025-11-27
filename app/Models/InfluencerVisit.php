<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfluencerVisit extends Model
{
    protected $fillable = [
        'influencer_name',
        'phone',
        'place',
        'influencer_type',
        'visit_type',
        'purpose',
        'district_id',
        'lead_type',
        'current_project',
        'upcoming_project',
        'steel_used',
        'other_steels',
        'total_deal_volume',
        'won_volume',
        'follow_up_date',
        'lost_volume', 
        'lost_to_competitor', 
        'competitor_name',
        'reason_for_lost', 
        'chain_id',
        'status',
        'created_by'
    ];
    protected $casts = [
        'steel_used' => 'array', 
        'follow_up_date' => 'date',

    ];
 
    public function createdBy()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }
    public function followUps()
    {
        return $this->hasMany(InfluencerVisitFollowUp::class, 'influencer_visit_id');
    }
    public function order()
    {
        return $this->hasOne(Order::class, 'influencer_visit_id');
    }
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }
}
