<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'activity_types';

    protected $fillable = [
        'name',
	'status',
	'created_by',
    ];

    protected $casts = [
        'status' => 'string', // Ensures enum is stored as string
    ];

    /**
     * Relationship: ActivityType has many Activities
     */
    public function activities()
    {
        return $this->hasMany(Activity::class, 'activity_type_id', 'id');
    }

    /**
     * Relationship: ActivityType has many Question Labels
     */
    public function questionLabels()
    {
        return $this->hasMany(ActivityQuestionLabel::class, 'activity_types_id', 'id');
    }
}
