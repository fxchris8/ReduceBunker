<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelBaseline extends Model
{
    protected $primaryKey = 'vessel_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'vessel_id',
        'static_bl_me',
        'static_bl_ae',
        'bl_l_nm',
        'dynamic_bl_me',
        'ae_parallel_2',
        'density',
        'speed',
        'ss_multiplier_me',
        'ss_multiplier_ae',
        'ss_multiplier_me_dynamic',
        'bl_ae_1_reffer',
    ];

    protected $casts = [
        'static_bl_me'             => 'integer',
        'static_bl_ae'             => 'integer',
        'bl_l_nm'                  => 'integer',
        'dynamic_bl_me'            => 'integer',
        'density'                  => 'integer',
        'speed'                    => 'integer',
        'ss_multiplier_me'         => 'integer',
        'ss_multiplier_ae'         => 'integer',
        'ss_multiplier_me_dynamic' => 'integer',
    ];

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class, 'vessel_id', 'vessel_id');
    }

    public function getSsMeAttribute(): float
    {
        return $this->static_bl_me * $this->ss_multiplier_me * 24;
    }

    public function getSsAeAttribute(): float
    {
        return $this->static_bl_ae * $this->ss_multiplier_ae * 24;
    }

    public function getSsBlMeDynamicAttribute(): float
    {
        return $this->dynamic_bl_me * $this->ss_multiplier_me_dynamic * 24;
    }
}