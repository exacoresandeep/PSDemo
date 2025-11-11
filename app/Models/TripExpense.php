<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TripExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'driver_id',
        'expense_type',
        'remarks',
        'amount',
        'current_km',
        'fuel_litre',
        'bill_image',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class, 'expense_type');
    }
}
