<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VesselConsumptionDaily extends Model
{
    protected $table = 'vessel_consumption_daily';

    protected $fillable = [
        'report_date',
        'vessel_id',
        'session_type',
        'me_mfo',
        'me_hsd',
        'ae_mfo',
        'ae_hsd',
        'boiler_hsd',
        'boiler_mfo',
        'genset_consum_hsd',
        'deck_daily_work',
        'engine_daily_work',
        'remarks',
        'steam_time',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'synced_at' => 'datetime',
            'steam_time' => 'decimal:2',
            'me_mfo' => 'decimal:2',
            'me_hsd' => 'decimal:2',
            'ae_mfo' => 'decimal:2',
            'ae_hsd' => 'decimal:2',
            'boiler_hsd' => 'decimal:2',
            'boiler_mfo' => 'decimal:2',
            'genset_consum_hsd' => 'decimal:2',        ];
    }
}
