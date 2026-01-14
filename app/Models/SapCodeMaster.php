<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SapCodeMaster extends Model
{
    protected $table = 'sap_code_master';

    protected $fillable = [
        'sap_id',
        'sap_code',
    ];

    public $timestamps = false;
}
