<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'activities';

    protected $fillable = [
        'activity_type_id',
        'dealer_id',
        'employee_id',
        'assigned_date',
        'due_date',
        'instructions',
        'status',
    	'notification_status',
    	'remarks',
        'attachments',
        "created_by",
        'completed_date'
    ];
    protected $casts = [
        'attachments' => 'array', 
        // 'completed_date' => 'date',
        // 'assigned_date' => 'date',
    ];
    

    public function activityType()
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id');
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
