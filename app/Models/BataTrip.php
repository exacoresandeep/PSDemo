<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BataTrip extends Model
{
    protected $fillable = ['bata_id','trip_code','salary_type','amount'];

    public function bata() {
        return $this->belongsTo(Bata::class);
    }
}
