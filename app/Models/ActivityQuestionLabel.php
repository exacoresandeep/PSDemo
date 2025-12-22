<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityQuestionLabel extends Model
{
    protected $table = 'activity_question_labels';

    protected $fillable = [
        'activity_types_id',
        'type',
        'label_name',
	'label_options',
	'created_by',
    ];

    public function activityType()
    {
        return $this->belongsTo(ActivityType::class, 'activity_types_id');
    }
}
