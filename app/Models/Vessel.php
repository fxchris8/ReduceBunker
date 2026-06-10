<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vessel extends Model
{
    protected $fillable = [
        'vessel_id',
        'vessel_name',
    ];

    public function baseline(): HasOne
    {
        return $this->hasOne(FuelBaseline::class, 'vessel_id');
    }
}