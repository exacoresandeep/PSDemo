<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TripStatusHistory extends Model
{
    use HasFactory;
    protected $table = 'trip_status_history';
    public $timestamps = false;
    protected $fillable = [
        'trip_id',
        'status',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(Employee::class, 'changed_by');
    }
}
