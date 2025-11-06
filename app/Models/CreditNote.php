<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'dealer_id',
        'credit_note_number',
        'invoice_number',
        'date',
        'returned_items', 
        'total_return_quantity',
        'total_row_amount',
    ];

    protected $casts = [
        'returned_items' => 'array', 
        'date' => 'date',
        'total_return_quantity' => 'float',
        'total_row_amount' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }
}
