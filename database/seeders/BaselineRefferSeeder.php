<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BaselineRefferSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'AKA' => 4,
            'APE' => 5,
            'ASG' => 7,
            'ASN' => 7,
            'ASR' => 7,
            'BAU' => 2,
            'BGI' => 2,
            'BKU' => 2,
            'BSA' => 2,
            'DER' => 4,
            'FOR' => 4,
            'HAN' => 13,
            'HAP' => 13,
            'HAS' => 13,
            'HAY' => 13,
            'HJE' => 6,
            'HSA' => 6,
            'HSG' => 0,
            'HSJ' => 5,
            'KAA' => 4,
            'KLE' => null,
            'LUZ' => 15,
            'MAA' => null,
            'MAG' => 4,
            'MAN' => 3,
            'MAR' => null,
            'MBU' => 4,
            'MDA' => null,
            'MEN' => null,
            'MHI' => null,
            'MIA' => null,
            'MPR' => null,
            'MSM' => null,
            'MTI' => null,
            'MVI' => null,
            'MWA' => null,
            'MYA' => null,
            'ODI' => 47,
            'OEM' => 15,
            'OGA' => 4,
            'OGO' => 4,
            'OJA' => null,
            'OPA' => 6,
            'ORU' => null,
            'OSA' => 7,
            'OSI' => 15,
            'PAH' => 2,
            'PBE' => 2,
            'PBI' => null,
            'PFA' => 3,
            'PGL' => null,
            'PHK' => 6,
            'PLA' => 6,
            'PNN' => 4,
            'PRA' => 4,
            'PRI' => 3,
            'PSM' => null,
            'PST' => 2,
            'PWE' => 6,
            'RAH' => 10,
            'RAT' => 8,
            'REN' => 10,
            'RET' => 10,
            'RUM' => 10,
            'SBR' => 4,
            'SLA' => null,
            'TBE' => 2,
            'TBI' => 2,
            'TFL' => 2,
            'TIT' => 6,
            'VEI' => 4,
            'VER' => 6,
            'XSA' => null,
            'YSA' => null,
        ];

        foreach ($data as $vesselId => $value) {
            DB::table('fuel_baselines')
                ->where('vessel_id', $vesselId)
                ->update(['bl_ae_1_reffer' => $value]);
        }
    }
}