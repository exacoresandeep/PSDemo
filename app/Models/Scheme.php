<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scheme extends Model
{
    protected $table = 'scheme';

    protected $fillable = [
        'scheme',
        'status',
    ];

    public $timestamps = false; 

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}