<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BataDeduction extends Model
{
    protected $fillable = ['bata_id','trip_code','reason','duration','amount','remarks'];

    public function bata() {
        return $this->belongsTo(Bata::class);
    }
}
