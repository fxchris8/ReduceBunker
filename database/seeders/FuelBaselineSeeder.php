<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FuelBaselineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $data = [
            ['vessel_id' => 'MHI', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'MSM', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'MPR', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'MEN', 'static_bl_me' => 160,   'static_bl_ae' => 40,   'dynamic_bl_me' => 185,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 11, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'MTI', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'MAR', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'MVI', 'static_bl_me' => 160,   'static_bl_ae' => 40,   'dynamic_bl_me' => 185,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 11, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'MDA', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'MWA', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'MYA', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'MAA', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'MIA', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'XSA', 'static_bl_me' => 160,   'static_bl_ae' => 40,   'dynamic_bl_me' => 185,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 11, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'HSJ', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 5,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'ODI', 'static_bl_me' => 400,   'static_bl_ae' => 100,  'dynamic_bl_me' => 460,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 47,   'speed' => 15, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'OJA', 'static_bl_me' => 400,   'static_bl_ae' => 100,  'dynamic_bl_me' => 460,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 15, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'ORU', 'static_bl_me' => 400,   'static_bl_ae' => 100,  'dynamic_bl_me' => 460,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 15, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'OSA', 'static_bl_me' => 400,   'static_bl_ae' => 100,  'dynamic_bl_me' => 460,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 7,    'speed' => 15, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'VEI', 'static_bl_me' => 320,   'static_bl_ae' => 80,   'dynamic_bl_me' => 370,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 4,    'speed' => 14, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'HAN', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 322,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 13,   'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'HAP', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 13,   'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'HAS', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 13,   'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'HAY', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 13,   'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'HJE', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 862,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 6,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'PSM', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'AKA', 'static_bl_me' => 1000,  'static_bl_ae' => 1000, 'dynamic_bl_me' => 1000, 'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 4,    'speed' => 14, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'DER', 'static_bl_me' => 320,   'static_bl_ae' => 80,   'dynamic_bl_me' => 370,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 4,    'speed' => 14, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'FOR', 'static_bl_me' => 320,   'static_bl_ae' => 80,   'dynamic_bl_me' => 370,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 4,    'speed' => 14, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'KAA', 'static_bl_me' => 320,   'static_bl_ae' => 80,   'dynamic_bl_me' => 370,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 4,    'speed' => 14, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'MAG', 'static_bl_me' => 400,   'static_bl_ae' => 100,  'dynamic_bl_me' => 460,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 4,    'speed' => 15, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'RAT', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 8,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'RUM', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 10,   'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'HSA', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 6,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'MBU', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 4,    'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'OGO', 'static_bl_me' => 400,   'static_bl_ae' => 100,  'dynamic_bl_me' => 460,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 4,    'speed' => 15, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'SBR', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 4,    'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'TIT', 'static_bl_me' => 320,   'static_bl_ae' => 80,   'dynamic_bl_me' => 370,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 6,    'speed' => 14, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'VER', 'static_bl_me' => 320,   'static_bl_ae' => 80,   'dynamic_bl_me' => 370,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 6,    'speed' => 14, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'APE', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 5,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'LUZ', 'static_bl_me' => 400,   'static_bl_ae' => 100,  'dynamic_bl_me' => 460,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 15,   'speed' => 15, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'OGA', 'static_bl_me' => 400,   'static_bl_ae' => 100,  'dynamic_bl_me' => 460,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 4,    'speed' => 15, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'PBE', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 2,    'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'PBI', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'PFA', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 3,    'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'PRI', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 3,    'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'ASG', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 7,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'ASN', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 418,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 7,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'ASR', 'static_bl_me' => 100,   'static_bl_ae' => 100,  'dynamic_bl_me' => 100,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 7,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'MAN', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 3,    'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'OEM', 'static_bl_me' => 400,   'static_bl_ae' => 100,  'dynamic_bl_me' => 460,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 15,   'speed' => 15, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'OSI', 'static_bl_me' => 400,   'static_bl_ae' => 100,  'dynamic_bl_me' => 460,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 15,   'speed' => 15, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'BAU', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 154,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 2,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'BGI', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 642,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 2,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'BKU', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 2,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'BSA', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 2,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'PAH', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 162,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 2,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'PST', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 2,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'KLE', 'static_bl_me' => 500,   'static_bl_ae' => 125,  'dynamic_bl_me' => 575,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 16, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'SLA', 'static_bl_me' => 500,   'static_bl_ae' => 125,  'dynamic_bl_me' => 575,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 16, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'RAH', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 10,   'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'RET', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 10,   'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'REN', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 10,   'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'PRA', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 4,    'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'PNN', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 4,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'PGL', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'HSG', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 0,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'PHK', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 6,    'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'PLA', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 6,    'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'PWE', 'static_bl_me' => 200,   'static_bl_ae' => 50,   'dynamic_bl_me' => 230,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 6,    'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'TBE', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 2,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'TBI', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 2,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'TFL', 'static_bl_me' => 260,   'static_bl_ae' => 65,   'dynamic_bl_me' => 300,  'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => 2,    'speed' => 13, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
            ['vessel_id' => 'OPA', 'static_bl_me' => 23040, 'static_bl_ae' => 6792, 'dynamic_bl_me' => 0,    'ae_parallel_2' => null, 'density' => 991, 'bl_ae_1_reffer' => null, 'speed' => 12, 'ss_multiplier_me' => 2, 'ss_multiplier_ae' => 2],
        ];

        foreach ($data as $row) {
            DB::table('fuel_baselines')
                ->where('vessel_id', $row['vessel_id'])
                ->update(array_merge($row, ['updated_at' => $now]));
        }
    }
}
