<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DayExpense extends Model
{
    use HasFactory;

    protected $table = 'day_expense'; // table name

    protected $fillable = [
        'employee_id',
        'travel_method',
        'km_traveled',
        'other_expense',
        'remarks',
        'attachment',
        'total_amount',
    ];
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
