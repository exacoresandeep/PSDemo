<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TyreCategory extends Model
{
    use HasFactory;

    protected $table = 'tyre_categories';

    protected $fillable = [
        'name',
        'status'
    ];
}
