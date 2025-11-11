<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssistanceImage extends Model
{
    use HasFactory;

    protected $fillable = ['assistance_id', 'image_path'];

    public function assistance()
    {
        return $this->belongsTo(Assistance::class);
    }
}