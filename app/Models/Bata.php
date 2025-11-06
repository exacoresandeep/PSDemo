<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bata extends Model
{
    protected $fillable = [
        'driver_id','phone','from_date','to_date','total_amount','deduction_amount','payable_amount'
    ];

    public function trips() {
        return $this->hasMany(BataTrip::class);
    }

    public function deductions() {
        return $this->hasMany(BataDeduction::class);
    }
    public function driver() {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}
