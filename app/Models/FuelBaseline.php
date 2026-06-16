<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelBaseline extends Model
{
    protected $fillable = [
        'vessel_id',
        'static_bl_me',
        'static_bl_ae',
        'dynamic_bl_me',
        'ae_parallel_2',
        'density',
        'speed',
        'ss_multiplier_me',
        'ss_multiplier_ae',
        'bl_ae_1_reffer',
    ];

    protected $casts = [
        'static_bl_me'      => 'integer',
        'static_bl_ae'      => 'integer',
        'dynamic_bl_me'     => 'integer',
        'density'           => 'integer',
        'speed'             => 'integer',
        'ss_multiplier_me'  => 'integer',
        'ss_multiplier_ae'  => 'integer',
    ];

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class, 'vessel_id', 'vessel_id');
    }

    public function getSsMeAttribute(): float
    {
        return $this->static_bl_me * $this->ss_multiplier_me;
    }

    public function getSsAeAttribute(): float
    {
        return $this->static_bl_ae * $this->ss_multiplier_ae;
    }
}