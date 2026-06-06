<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelBaseline extends Model
{
    protected $fillable = [
        'vessel_id',
        'bl_mfo',
        'bl_hsd',
        'speed',
        'ss_multiplier_mfo',
        'ss_multiplier_hsd',
    ];

    protected $casts = [
        'bl_mfo'            => 'integer',
        'bl_hsd'            => 'integer',
        'speed'              => 'integer',
        'ss_multiplier_mfo' => 'integer',
        'ss_multiplier_hsd' => 'integer',
    ];

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class, 'vessel_id', 'vessel_id');
    }

    public function getSsMfoAttribute(): float
    {
        return $this->bl_mfo * $this->ss_multiplier_mfo;
    }

    public function getSsHsdAttribute(): float
    {
        return $this->bl_hsd * $this->ss_multiplier_hsd;
    }
}