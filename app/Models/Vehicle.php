<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_no',
        'vehicle_code',
        'vehicle_category_id',
        'vehicle_type_id',
        'vehicle_type_text',
        'transport_provider_name',
        'transporter_phone',
        'model',
        'year_of_manufacture',
        'fuel_type',
        'load_capacity',
        'inspection_days',
        'rent_amount',
        'bata_amount',
        'rc_exp_date',
        'rc_file',
        'insurance_no',
        'insurance_type',
        'insurance_exp_date',
        'insurance_file',
        'puc_no',
        'puc_exp_date',
        'puc_file',
        'fitness_no',
        'fitness_exp_date',
        'fitness_file',
        'permit_no',
        'permit_exp_date',
        'permit_file',
        'status',
        'starting_km',
        'last_inspection_km',
        'inspection_km',
        'service_days',
        'last_service_date',
        'last_service_km',
        'service_km',
        'chasis_no',                 
        'engine_no',                 
        'owner_name',                 
        'vehicle_tax_amount',        
        'tax_valid_upto',            
        'tax_receipt_file',          
        'premium_amount',             
        'national_permit_valid_upto', 
        'national_permit_file',       
        'authorization_date',  
    ];

    protected $dates = ['deleted_at'];

    protected $casts = [
        'last_service_date' => 'datetime',
        'inspection_km'     => 'integer',
        'last_service_km'   => 'integer',
        'service_days'      => 'integer',

        'rc_exp_date' => 'date',
        'tax_valid_upto' => 'date',
        'insurance_exp_date' => 'date',
        'puc_exp_date' => 'date',
        'fitness_exp_date' => 'date',
        'permit_exp_date' => 'date',
        'national_permit_valid_upto' => 'date',
        'authorization_date' => 'date',
    ];

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class, 'vehicle_id');
    }

    public function maintenanceReports()
    {
        return $this->hasMany(MaintenanceReport::class, 'vehicle_id');
    }
    public function inspections()
    {
        return $this->hasMany(VehicleInspection::class, 'vehicle_id');
    }
}
