<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DealerTarget extends Model
{
    use HasFactory, SoftDeletes; 

    protected $table = 'dealer_targets';

    protected $fillable = [
        'dealer_id',     
        'month',
        'year',
        'order_quantity',
        'created_by',
        'notification_status',
        'product_id'
    ];

    protected $dates = ['deleted_at']; 
    
    public function dealer()
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

}
