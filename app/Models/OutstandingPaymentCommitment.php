<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutstandingPaymentCommitment extends Model
{
    use HasFactory;

    protected $fillable = [
        'outstanding_payment_id',
        'employee_id',  
        'committed_date',
        'committed_amount',
        'notification_status'
    ];

    public $timestamps = false;

    public function outstandingPayment()
    {
        return $this->belongsTo(OutstandingPayment::class);
    }
     public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

