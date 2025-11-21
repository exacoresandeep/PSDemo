<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Target extends Model
{
    use HasFactory, SoftDeletes; 

    protected $table = 'targets';

    protected $fillable = [
        'employee_id',     
        'month',
        'year',
        'unique_lead',
        'customer_visit',
        'activity_visit',
        'aashiyana',
        'order_quantity',
        'created_by',
        'notification_status',
        'product_id'
    ];

    protected $dates = ['deleted_at']; 
    
    public function customerType()
    {
        return $this->belongsTo(CustomerType::class, 'customer_types_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function employeeType()
    {
        return $this->belongsTo(EmployeeType::class, 'employee_type_id');
    }
}
