<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealerActivityItem extends Model
{
    protected $table = 'dealer_activity_item';

    protected $fillable = [
        'type',
        'status',
        'created_at',
    ];

    public $timestamps = false; 
}
