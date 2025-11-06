<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TyreManagement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tyre_management';

    protected $fillable = [
        'vehicle_id',
        'tyre_category_id',
        'stencil_number',
        'tyre_dimension',
        'tyre_pattern',
        'tyre_brand',
        'approx_durability_km',
        'tyre_type_id',
        'last_resoled_date',
        'resoled_count',
        'km_run',
        'status',
    ];

    protected $casts = [
        'last_resoled_date' => 'date',
        'resoled_count' => 'integer',
        'km_run' => 'integer',
        'approx_durability_km' => 'integer',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function tyreCategory()
    {
        return $this->belongsTo(TyreCategory::class, 'tyre_category_id');
    }

    public function tyreType()
    {
        return $this->belongsTo(TyreType::class, 'tyre_type_id');
    }
}
