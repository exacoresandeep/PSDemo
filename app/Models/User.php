<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
<<<<<<< HEAD
=======
        'product_ids',
>>>>>>> origin/master
        'role_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
<<<<<<< HEAD
=======
            'product_ids' => 'array',
>>>>>>> origin/master
            'password' => 'hashed',
        ];
    }
    public function isSuperAdmin()
    {
        return $this->role_id == 1;
    }

    public function isSales()
    {
        return $this->role_id == 2;
    }

    public function isAccounts()
    {
        return $this->role_id == 3;
    }
<<<<<<< HEAD
=======

    public function role()
    {
        return $this->belongsTo(UserType::class, 'role_id');
    }
>>>>>>> origin/master
}
