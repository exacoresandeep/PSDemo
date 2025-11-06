<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTerms extends Model
{
    use HasFactory;

    protected $table = 'payment_terms'; 

    protected $fillable = ['name', 'status', 'created_at'];

    public $timestamps = true; 

    // public function scopeActive($query)
    // {
    //     return $query->where('status', 1);
    // }
}

