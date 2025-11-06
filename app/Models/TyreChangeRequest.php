<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TyreChangeRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tyre_change_requests';

    protected $fillable = [
        'vehicle_id',
        'current_kilometer',
        'reason',
        'tyre_category_id',
        'tyre_management_id',
        'stencil_number',
        'tyre_dimension',
        'tyre_pattern',
        'tyre_brand',
        'approx_durability_km',
        'axle_type_id',
        'tyre_type_id',
        'status',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function tyreCategory()
    {
        return $this->belongsTo(TyreCategory::class, 'tyre_category_id');
    }

    public function tyreManagement()
    {
        return $this->belongsTo(TyreManagement::class, 'tyre_management_id');
    }

    public function tyreType()
    {
        return $this->belongsTo(TyreType::class, 'tyre_type_id');
    }

    public function axleType()
    {
        return $this->belongsTo(AxleType::class, 'axle_type_id');
    }
}
