<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivitiesQuestionDetail extends Model
{
    protected $table = 'activities_question_details';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'activity_id',
        'activity_question_labels_id',
        'activity_input',
    ];

    // Relationships (if applicable)
    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function questionLabel()
    {
        return $this->belongsTo(ActivityQuestionLabel::class, 'activity_question_labels_id');
    }
}
