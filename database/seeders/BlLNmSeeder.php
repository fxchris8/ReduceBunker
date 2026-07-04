<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlLNmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'AKA'   => 20,
            'APE'   => 75,
            'ASG'   => 40,
            'ASN'   => 43,
            'ASR'   => 42,
            'BAU'   => 23,
            'BGI'   => 23,
            'BKU'   => 23,
            'BSA'   => 23,
            'DER'   => 20,
            'FOR'   => 20,
            'HAN'   => 39,
            'HAP'   => 44,
            'HAS'   => 38,
            'HAY'   => 43,
            'HJE'   => 65,
            'HSA'   => 83,
            'HSG'   => 65,
            'HSJ'   => 73,
            'KAA'   => 23,
            'KLE'   => 32,
            'LUZ'   => 90,
            'MAA'   => 125,
            'MAG'   => 20,
            'MAN'   => 35,
            'MAR'   => 110,
            'MBU'   => 30,
            'MDA'   => 120,
            'MEN'   => 115,
            'MHI'   => 100,
            'MIA'   => 130,
            'MPR'   => 110,
            'MSM'   => 100,
            'MTI'   => 100,
            'MVI'   => 160,
            'MWA'   => 117,
            'MYA'   => 105,
            'ODI'   => 191,
            'OEM'   => 95,
            'OGA'   => 100,
            'OGO'   => 90,
            'OJA'   => 110,
            'OPA'   => 40,
            'ORU'   => 110,
            'OSA'   => 40,
            'OSI'   => 95,
            'PAH'   => 20,
            'PBE'   => 46,
            'PFA'   => 35,
            'PHK'   => 40,
            'PLA'   => 40,
            'PNN'   => 41,
            'PRA'   => 20,
            'PRI'   => 35,
            'PSM'   => 54,
            'PST'   => 20,
            'PWE'   => 38,
            'RAH'   => 30,
            'RAT'   => 40,
            'REN'   => 30,
            'RET'   => 30,
            'RUM'   => 35,
            'SBR'   => 30,
            'SLA'   => 38,
            'TBE'   => 25,
            'TFL'   => 25,
            'TIT'   => 32,
            'VEI'   => 65,
            'VER'   => 32,
            'XSA'   => 135,
            'AOBTB' => 75,
            'ABBRW' => 20,
            'RPPCU' => 20,
            'EIFLG' => 40,
        ];

        foreach ($data as $vesselId => $BlLNm) {
            DB::table('fuel_baselines')
                ->where('vessel_id', $vesselId)
                ->update(['bl_l_nm' => $BlLNm]);
        }
    }
}
