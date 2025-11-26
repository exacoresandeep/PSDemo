<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealerVisit extends Model
{
    protected $fillable = [
        'dealer_id',
        'aso_id',
        'purpose_of_visit',
        'item_type',
        'remarks',
        'attachments',
        'stock_details',
        'new_order',
        'created_by',
        'created_at',
    ];
     protected $casts = [
        'attachments' => 'array', // store as JSON
        'stock_details' => 'array', // store as JSON
    ];
    public $timestamps = false; 

    // Relationships
    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }

    public function aso()
    {
        return $this->belongsTo(Employee::class, 'aso_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function createdBy()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }
    public function order()
    {
        return $this->hasOne(Order::class, 'dealer_visit_id')->where('source', 'dealer_visit');
    }
}
