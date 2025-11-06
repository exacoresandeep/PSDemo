<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_report_id',
        'service_date',
        'current_kilometer',
        'job_details', 
        'bill_file',
        'cost',
        'total_cost',
        'labour_cost',
        'spare_cost',
        'next_service_km',
        'due_days',
    ];

    protected $casts = [
        'job_details' => 'array', 
    ];

    public function maintenanceReport()
    {
        return $this->belongsTo(MaintenanceReport::class);
    }
}
