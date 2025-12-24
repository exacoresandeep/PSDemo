<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeProductSap extends Model
{
    use HasFactory;

    protected $table = 'employee_product_sap';

    protected $fillable = [
        'employee_id',
        'product_id',
        'sap_code',
    ];

   
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
