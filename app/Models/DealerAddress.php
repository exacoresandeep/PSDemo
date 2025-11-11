<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealerAddress extends Model
{
    use HasFactory;
    protected $table = 'dealer_address';
    protected $fillable = [
        'dealer_id',
        'address_type',
        'address',
    ];

    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }
}


