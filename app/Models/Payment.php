<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'dealer_id',
        'invoice_number',
        'invoice_date',
        'payment_amount',
        'payment_date',
        'payment_document_no',
    ];

    protected $casts = [
        'payment_amount' => 'float',
        'payment_date' => 'date',
        'invoice_date' => 'date',
    ];

    public function dealer()
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'invoice_number', 'invoice_number');
    }
}
