<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutstandingNew extends Model
{
    protected $table = 'outstanding_new';

    protected $fillable = [
        'dealer_id',
        'product_id',
        'outstanding_amount',
        'due_balance'
        
    ];
    public function dealers()
    {
        return $this->belongsTo(Dealer::class);
    }
    public function dealer()
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

