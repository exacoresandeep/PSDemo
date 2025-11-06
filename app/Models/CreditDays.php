<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditDays extends Model
{
    protected $table = 'credit_days';

    protected $fillable = [
        'days',
        'status',
    ];

    public $timestamps = false; 
}