<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealerAddresses extends Model
{
    use HasFactory;
    protected $table = 'dealer_addresses';
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