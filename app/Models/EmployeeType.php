<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeType extends Model
{
    use HasFactory;

    protected $table = 'employee_types';

    protected $fillable = ['type_name'];

     public function employees()
    {
        return $this->hasMany(Employee::class, 'employee_type_id'); 
    }
}


