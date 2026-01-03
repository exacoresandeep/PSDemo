<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'employee_id',
        'date',
        'punch_in',
        'punch_out',
        'latitude',
        'longitude',
        'total_active_hours',
        'starting_remarks',
        'ending_remarks',
        'starting_km',
	'leave_type',
	'ending_km',
        'status',
        'starting_attachment',
        'ending_attachment'
    ];
    protected $casts = [
        'latitude' => 'string',
        'longitude' => 'string',
        'starting_km' => 'double',
        'ending_km'   => 'double',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

}
