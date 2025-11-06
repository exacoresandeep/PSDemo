<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Driver extends Model
{
    use SoftDeletes, HasFactory, HasApiTokens;

    protected $fillable = [
        'name',
        'phone',
        'alternative_phone',
        'address',
        'username',
        'password',
        'district_id',
        'pincode',
        'adharcard_no',
        'adhar_attachment',
        'liscence_no',
        'liscence_attachment',
        'liscence_exp_date',
        'blood_group',
        'status',
        'password_reset_flag',
        'photo',
        'dob'
    ];


    protected $casts = [
        'deleted_at' => 'datetime',
    ];
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }
}
