<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use DateTime;

class ConsumptionController extends Controller
{
    function reorderReport($grouped, $baselines, int $reportId = 16)
    {
        $vesselRaw = $grouped['vesselid'] ?? '';
        $vesselKey = strtoupper(trim($vesselRaw));
        $vesselBaseline = $baselines->get($vesselKey);

        $bl_l_nm = $vesselBaseline?->bl_l_nm ?? 10;

        $bl_me  = $vesselBaseline?->static_bl_me ?? 0;
        $bl_ae  = $vesselBaseline?->static_bl_ae ?? 0;
        $bl_ae_1_reffer = $vesselBaseline?->bl_ae_1_reffer ?? 0;
        $ae_parallel_2  = $vesselBaseline?->ae_parallel_2 ?? 0;

        $ordered = [
            'Vessel ID' => $grouped['vesselid'] ?? null,
            'tanggal'   => isset($grouped['tanggal'])
                ? (new DateTime($grouped['tanggal']))->format('d F Y H:i')
                : null,
            'POSITION'                          => $grouped['pos'] ?? null,
            'DEPARTURE PORT'                    => $grouped['departure'] ?? null,
            'DESTINATION'                       => $grouped['destination'] ?? null,
            'STEAM. DIST.'                      => $grouped['steam_dist'] ?? null,
            'STEAM TIME (HOUR : MINUTE)'        => $grouped['steam_time'] ?? null,
            'SHIP SPEED'                        => $grouped['ship_speed'] ?? null,
            'PROPELLER SLIP'                    => $grouped['prop_slip'] ?? null,
            'ME RPM'                            => $grouped['me_rpm'] ?? null,
            'M/E MFO'                           => $grouped['me_mfo'] ?? null,
            'M/E HSD'                           => $grouped['me_hsd'] ?? null,
            'A/E MFO'                           => $grouped['ae_mfo'] ?? null,
            'A/E HSD'                           => $grouped['ae_hsd'] ?? null,
            'MANEUVERING TIME (HOURS)'          => $grouped['duration_manuev'] ?? null,
            'BOILER HSD'                        => $grouped['boiler_hsd'] ?? null,
            'BOILER MFO'                        => $grouped['boiler_mfo'] ?? null,
            'GENSET CONSUMPTION - HSD'          => $grouped['genset_consum_hsd'] ?? null,
            'EMERGENCY GENERATOR CONSUMPTION'   => $grouped['emg'] ?? null,
            'TOTAL CRANE'                       => $grouped['total_crane'] ?? null,
            'CRANE DURATION'                    => $grouped['crane_duration'] ?? null,
            'LOAD A/E 1 (KW)'                   => $grouped['load_ae_1'] ?? null,
            'LOAD A/E 2 (KW)'                   => $grouped['load_ae_2'] ?? null,
            'LOAD A/E 3 (KW)'                   => $grouped['load_ae_3'] ?? null,
            'LOAD A/E 4 (KW)'                   => $grouped['load_ae_4'] ?? null,
            'AE PARAREL DURATION'               => $grouped['ae_pararel_duration'] ?? null,
            'REEFER 20"'                        => $grouped['reefer20'] ?? null,
            'REEFER 40"'                        => $grouped['reefer40'] ?? null,
            'ME Maneuvering Cons. (L/H)'        => $grouped['me_manuev_consum'] ?? null,
            'SELISIH ME Maneuvering'            => ($bl_me * 24) - ($grouped['me_manuev_consum'] ?? 0),
            'BL M/E Static (L/Day)'             => $bl_me * 24,
            'BL L/NM'                           => $bl_l_nm,
            'BL MFO'                            => $bl_me,
            'BL HSD'                            => $bl_ae,
            'BL REFFER'                         => $bl_ae_1_reffer,
            'BL AE PARALLEL 2'                  => $ae_parallel_2,
            'L/NM' => (
                isset($grouped['steam_time']) && $grouped['steam_time'] >= 24 && !empty($grouped['steam_dist'])
            ) ? (
                (($grouped['me_mfo'] ?? 0) + ($grouped['me_hsd'] ?? 0)) / $grouped['steam_dist']
            ) : 0,
            'EXCESS ME L/NM (%)' => (
                isset(
                $bl_l_nm,
                $grouped['steam_time'], $grouped['steam_dist']) && $grouped['steam_time'] >= 24 && !empty($grouped['steam_dist'])
            ) ? (
                (((($grouped['me_mfo'] ?? 0) + ($grouped['me_hsd'] ?? 0)) / $grouped['steam_dist']) > 0)
                    ? (($bl_l_nm - ((($grouped['me_mfo'] ?? 0) + ($grouped['me_hsd'] ?? 0)) / $grouped['steam_dist'])) / $bl_l_nm) * 100
                    : 0
            ) : 0,
            'EXCESS ME' => ($bl_me * (($grouped['steam_time'] ?? 0) + ($grouped['duration_manuev'] ?? 0)))
                - (($grouped['me_mfo'] ?? 0) + ($grouped['me_hsd'] ?? 0)),
            'BL A/E (L/Day)'    => $bl_ae * 24,
            'AE Consumption'    => ($grouped['ae_hsd'] ?? 0) + ($grouped['ae_mfo'] ?? 0) + ($grouped['genset_consum_hsd'] ?? 0),
            'EXCESS AE'         => ($bl_ae * 24) - (($grouped['ae_hsd'] ?? 0) + ($grouped['ae_mfo'] ?? 0) + ($grouped['genset_consum_hsd'] ?? 0)),
            'EXCESS AE Tolerance' => ($bl_ae * 24)
                + (($grouped['ae_pararel_duration'] ?? 0) * $bl_ae)
                - (($grouped['ae_hsd'] ?? 0) + ($grouped['ae_mfo'] ?? 0) + ($grouped['genset_consum_hsd'] ?? 0)),
            ];

        if ($reportId === 16) {
            $ordered['REMARKS']           = $grouped['remarks'] ?? '-';
            $ordered['DECK DAILY WORK']   = $grouped['deck_daily_work'] ?? '-';
            $ordered['ENGINE DAILY WORK'] = $grouped['engine_daily_work'] ?? '-';
        }

        return $ordered;
    }

    private function polyfitQuadratic(array $x, array $y)
    {
        $n = min(count($x), count($y));

        if ($n < 3) {
            return [0, 0, 0];
        }

        // Hitung jumlah-sum yang diperlukan
        $sumX = $sumX2 = $sumX3 = $sumX4 = 0;
        $sumY = $sumXY = $sumX2Y = 0;

        for ($i = 0; $i < $n; $i++) {
            $xi = $x[$i];
            $yi = $y[$i];

            $sumX   += $xi;
            $sumX2  += $xi**2;
            $sumX3  += $xi**3;
            $sumX4  += $xi**4;

            $sumY   += $yi;
            $sumXY  += $xi * $yi;
            $sumX2Y += ($xi**2) * $yi;
        }

        // Matriks normal equations:
        // [sumX4 sumX3 sumX2] [a]   [sumX2Y]
        // [sumX3 sumX2 sumX ] [b] = [sumXY ]
        // [sumX2 sumX  n    ] [c]   [sumY  ]

        $A = [
            [$sumX4, $sumX3, $sumX2],
            [$sumX3, $sumX2, $sumX ],
            [$sumX2, $sumX , $n    ]
        ];
        $B = [$sumX2Y, $sumXY, $sumY];

        // Selesaikan sistem persamaan linear A*[a,b,c] = B
        $coeff = $this->solveLinearSystem($A, $B);

        return $coeff; // [a, b, c]
    }

    private function solveLinearSystem(array $A, array $B)
    {
        // Gunakan eliminasi Gauss sederhana
        $n = count($B);
        for ($i = 0; $i < $n; $i++) {
            // Pivot
            $maxRow = $i;
            for ($k = $i+1; $k < $n; $k++) {
                if (abs($A[$k][$i]) > abs($A[$maxRow][$i])) {
                    $maxRow = $k;
                }
            }
            // Tukar baris
            [$A[$i], $A[$maxRow]] = [$A[$maxRow], $A[$i]];
            [$B[$i], $B[$maxRow]] = [$B[$maxRow], $B[$i]];

            // Eliminasi
            for ($k = $i+1; $k < $n; $k++) {
                $c = $A[$k][$i] / $A[$i][$i];
                for ($j = $i; $j < $n; $j++) {
                    $A[$k][$j] -= $c * $A[$i][$j];
                }
                $B[$k] -= $c * $B[$i];
            }
        }

        // Back substitution
        $x = array_fill(0, $n, 0);
        for ($i = $n-1; $i >= 0; $i--) {
            $sum = $B[$i];
            for ($j = $i+1; $j < $n; $j++) {
                $sum -= $A[$i][$j] * $x[$j];
            }
            $x[$i] = $sum / $A[$i][$i];
        }
        return $x;
    }

    private function hitungKonsumsi(array $report, array $titik, float $density, array $konstan_kurva, array $all_vessels)
    {
        $selectedVessel = strtoupper($report['Vessel ID']);
        $power_kw = floatval($report['DAYA ME (KW)'] ?? 0);
        $steam_time = floatval($report['STEAM TIME (HOUR : MINUTE)'] ?? 0);

        $titik_x_ori_raw = array_map('floatval', $titik[$selectedVessel]['X'] ?? []);
        $titik_y_ori_raw = array_map('floatval', $titik[$selectedVessel]['Y'] ?? []);

        $kapal_kecil = ['PHK', 'PLA', 'PWE', 'TBE', 'TBI', 'TFL',
                        'BAU', 'BKU', 'BSA', 'BGI', 'PAH', 'PST',
                        'PRA', 'HAN', 'HAP', 'HAS', 'HAY', 'FOR',
                        'AKA', 'DER', 'MAG', 'KAA', 'OJA', 'ORU',
                        'REN', 'PBE', 'LUZ', 'PSM', 'PNN', 'RUM',
                        'RAT', 'OPA', 'OSA', 'ASR', 'ASN', 'ASG',
                        'RET', 'RAH'];

        $kapal_osi_oem = ['OSI', 'OEM'];

        $kapal_konstan = ['APE', 'PFA', 'PRI', 'ODI'];
        $sfoc_konstan_bhp = ['APE', 'ODI'];

        if(!in_array($selectedVessel, $all_vessels)){
            return null;
        }

        if (in_array($selectedVessel, $kapal_kecil)){
            ///// X = kW //////////
            ///// Y = g/Kw/hr //////

            //////// convert titik ///////////
            $titik_x_convert = array_map(fn($x) => $x, $titik_x_ori_raw);
            $titik_y_convert = array_map(fn($y) => $y / $density, $titik_y_ori_raw);
        }
        elseif (in_array($selectedVessel, $kapal_osi_oem)){
            ///// X = kW //////
            ///// Y = g/BHP/hr //////

            $titik_x_convert = $titik_x_ori_raw;
            $titik_y_convert = array_map(fn($y) => $y / 0.7457 / $density, $titik_y_ori_raw);
        }
        elseif (in_array($selectedVessel, $kapal_konstan)){
            $titik_x_convert = null;
            $titik_y_convert = null;
        }
        else {
            ////// X = BHP //////
            ////// Y = g/BHP/hr ////

            //////// convert titik ///////////
            $titik_x_convert = array_map(fn($x) => $x * 0.7457, $titik_x_ori_raw);
            $titik_y_convert = array_map(fn($y) => $y / 0.7457 / $density, $titik_y_ori_raw);
        }

        ///// cari koefisien //////

        if (!in_array($selectedVessel, $kapal_konstan)){

            $koefisien_convert = $this->polyfitQuadratic($titik_x_convert, $titik_y_convert);

            list($d, $e, $f) = $koefisien_convert;

            //// perhitungan sfoc dan konsumsi dari grafik bhp ////

            $sfoc_kw = $d * ($power_kw ** 2) + $e * $power_kw + $f;

            $konsumsi = $sfoc_kw * $power_kw * $steam_time;
            $konsumsi = ceil($konsumsi);

            if ($power_kw == 0){
                $sfoc_kw = 0;
                $konsumsi = 0;
            }
        }
        else {
            if (in_array($selectedVessel, $sfoc_konstan_bhp)){
                $sfoc_kw = $konstan_kurva[$selectedVessel]['SFOC'] / 0.7457 / $density;
            }
            else {
                $sfoc_kw = $konstan_kurva[$selectedVessel]['SFOC'] / $density;
            }

            $konsumsi = $sfoc_kw * $power_kw * $steam_time;
        }
        return $konsumsi;
    }

    private function reorderDinamisReport($grouped)
    {
        $ordered = [
            'Vessel ID' => $grouped['vesselid'] ?? null,
            'tanggal' => isset($grouped['tanggal'])
                ? (new DateTime($grouped['tanggal']))->format('d F Y H:i')
                : null,

            'POSITION' => $grouped['pos'] ?? null,

            'DEPARTURE PORT' => $grouped['departure'] ?? null,
            'DESTINATION' => $grouped['destination'] ?? null,

            'STEAM. DIST.' => $grouped['steam_dist'] ?? null,
            'SHIP SPEED' => $grouped['ship_speed'] ?? null,

            'STEAM TIME (HOUR : MINUTE)' => $grouped['steam_time'] ?? null,
            'DAYA ME (KW)' => $grouped['daya_me_kw'] ?? null,
            'Konsumsi M/E MFO Aktual' => $grouped['me_mfo'] ?? null,

            'Konsumsi M/E MFO Perhitungan' => '',
            'Gap' => '',
            'Error' => '',
            ];

        return $ordered;
    }

    public function show(Request $request)
    {
        set_time_limit(1200);

        if (!$request->filled('report_date')) {
            return view('pages.consumption', [
                'report14'      => null,
                'report16'      => null,
                'port_sea_data' => null,
                'isDinamis'     => false,
                'density'       => 950,
            ]);
        }

        $reportDate = $request->input('report_date', date('Y-m-d', strtotime('-1 day')));
        $formattedDate = \Carbon\Carbon::parse($reportDate)->format('d/m/Y');

        $basePayload = [
            "tanggal" => $formattedDate,
        ];

        $reportIds = [14, 16];
        $allReports = [];
        $rawSeaReports = [];

        $baselines = \App\Models\FuelBaseline::all()->keyBy('vessel_id');

        foreach ($reportIds as $reportId) {
            $payload = $basePayload;
            $payload['report_id'] = (string)$reportId;

            if (False) {
                $data = ($reportId == 14) ? $this->getMockPortData() : $this->getMockSeaData();
            } else {
                $response = Http::timeout(120)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->withBody(json_encode($payload), 'application/json')
                ->get('http://nanika.spil.co.id:3021/get-bunker-analysis');

                if (!$response->successful()) {
                    return view('pages.consumption', [
                        'error' => 'Gagal ambil data API (report_id: '.$reportId.', status: '.$response->status().')',
                        'report14' => [],
                        'report16' => [],
                    ]);
                }

                $data = $response->json();
            }

            $reports = $data['data'] ?? [];

            if ($reportId === 16) {
                $rawSeaReports = $reports;
            }

            $normalized = array_map(fn($r) => $this->reorderReport($r, $baselines, $reportId), $reports);

            $allReports[$reportId] = $normalized;
        }

        $port_data = $allReports[14] ?? [];
        $sea_data = $allReports[16] ?? [];

        function deduplicateByVesselId(array $reports): array {
            $seen = [];
            $filtered = [];

            foreach ($reports as $report) {
                $vesselid = $report['Vessel ID'] ?? null;
                if ($vesselid && !in_array($vesselid, $seen)) {
                    $seen[] = $vesselid;
                    $filtered[] = $report;
                }
            }

            return $filtered;
        }

        $port_data = deduplicateByVesselId($port_data);
        $sea_data  = deduplicateByVesselId($sea_data);

        $sea_index = [];
        foreach ($sea_data as $sea) {
            $sea_index[$sea['Vessel ID']] = $sea;
        }

        $port_sea_data = [];
        foreach ($port_data as $port) {
            $vesselId = $port['Vessel ID'];
            if (isset($sea_index[$vesselId])) {
                $merged = [];

                foreach ($port as $key => $val) {
                    $merged[$key] = ['port' => $val, 'sea' => $sea_index[$vesselId][$key] ?? null];
                }

                foreach ($sea_index[$vesselId] as $key => $val) {
                    if (!isset($merged[$key])) {
                        $merged[$key] = ['port' => null, 'sea' => $val];
                    }
                }

                $port_sea_data[] = $merged;
            }
        }

        $greenColumns = ['BL M/E Static (L/Day)', 'BL A/E (L/Day)', 'BL L/NM'];
        $analysisColumns = ['SELISIH ME Maneuvering', 'EXCESS AE', 'EXCESS ME L/NM (%)'];

        $colored_port = array_map(function ($row) use ($greenColumns, $analysisColumns, $baselines) {
            $meHsd      = $row['M/E HSD'] ?? null;
            $maneuvering = $row['MANEUVERING TIME (HOURS)'] ?? null;
            $crane_dur  = $row['CRANE DURATION'] ?? null;
            $ae_par_dur = $row['AE PARAREL DURATION'] ?? null;

            $load_1 = $row['LOAD A/E 1 (KW)'] ?? null;
            $load_2 = $row['LOAD A/E 2 (KW)'] ?? null;
            $load_3 = $row['LOAD A/E 3 (KW)'] ?? null;
            $load_4 = $row['LOAD A/E 4 (KW)'] ?? null;

            $loads       = [$load_1, $load_2, $load_3, $load_4];
            $activeLoads = array_filter($loads, fn($v) => is_numeric($v) && $v > 0);

            $vesselBaseline = $baselines->get($row['Vessel ID'] ?? null);
            $blAe1Reffer    = $vesselBaseline?->bl_ae_1_reffer;
            $totalReefer    = floatval($row['REEFER 20"'] ?? 0) + floatval($row['REEFER 40"'] ?? 0);

            $allAeZero     = collect($loads)->every(fn($v) => !is_numeric($v) || floatval($v) == 0);
            $reeferExceeds = count($activeLoads) > 1 && $blAe1Reffer !== null && $totalReefer < $blAe1Reffer;
            $rowAnomalous  = ($allAeZero && $totalReefer > 0) || $reeferExceeds;

            $me_without_manuev_Condition    = is_numeric($meHsd) && $meHsd != 0 && is_numeric($maneuvering) && $maneuvering == 0;
            $excess_ae_par_dur_Condition    = is_numeric($ae_par_dur) && is_numeric($maneuvering) && is_numeric($crane_dur)
                                                && ($ae_par_dur - $maneuvering - $crane_dur > 3);
            $isLoadCondition                = count($activeLoads) > 1 && is_numeric($ae_par_dur) && $ae_par_dur == 0;
            $isLoadDifferentCondition       = count($activeLoads) > 1 && count(array_unique($activeLoads)) > 1;
            $isSingleLoadCondition          = count($activeLoads) === 1 && is_numeric($ae_par_dur) && $ae_par_dur != 0;
            $is24HoursAePararelCondition    = is_numeric($ae_par_dur) && $ae_par_dur == 24;

            $aeLoadCols = ['LOAD A/E 1 (KW)', 'LOAD A/E 2 (KW)', 'LOAD A/E 3 (KW)', 'LOAD A/E 4 (KW)', 'AE PARAREL DURATION', 'REEFER 20"', 'REEFER 40"'];
            $highlightAeLoad = $isLoadCondition || $isLoadDifferentCondition || $isSingleLoadCondition || $is24HoursAePararelCondition;

            $newRow = [];
            foreach ($row as $key => $value) {
                $class = '';

                if (in_array($key, $greenColumns)) {
                    $class .= ' bg-green-200 font-semibold';
                }
                if (in_array($key, $analysisColumns) && is_numeric($value) && $value < 0) {
                    $class .= ' bg-red-200 font-semibold';
                }
                if ($me_without_manuev_Condition && in_array($key, ['M/E HSD', 'MANEUVERING TIME (HOURS)'])) {
                    $class .= ' bg-yellow-200 font-semibold';
                }
                if ($excess_ae_par_dur_Condition && in_array($key, ['AE PARAREL DURATION', 'MANEUVERING TIME (HOURS)', 'CRANE DURATION'])) {
                    $class .= ' bg-blue-200 font-semibold';
                }
                if ($highlightAeLoad && in_array($key, $aeLoadCols)) {
                    $class .= ' bg-yellow-200 font-semibold';
                }

                $newRow[$key] = ['value' => $value, 'class' => $class];
            }

            $newRow['_row_class'] = ['value' => $rowAnomalous ? 'bg-red-200' : '', 'class' => ''];

            return $newRow;
        }, $port_data);

        $colored_sea = array_map(function ($row) use ($greenColumns, $analysisColumns, $baselines) {
            $meHsd       = $row['M/E HSD'] ?? null;
            $maneuvering = $row['MANEUVERING TIME (HOURS)'] ?? null;
            $crane_dur   = $row['CRANE DURATION'] ?? null;
            $ae_par_dur  = $row['AE PARAREL DURATION'] ?? null;

            $load_1 = $row['LOAD A/E 1 (KW)'] ?? null;
            $load_2 = $row['LOAD A/E 2 (KW)'] ?? null;
            $load_3 = $row['LOAD A/E 3 (KW)'] ?? null;
            $load_4 = $row['LOAD A/E 4 (KW)'] ?? null;

            $loads       = [$load_1, $load_2, $load_3, $load_4];
            $activeLoads = array_filter($loads, fn($v) => is_numeric($v) && $v > 0);

            $vesselBaseline = $baselines->get($row['Vessel ID'] ?? null);
            $blAe1Reffer    = $vesselBaseline?->bl_ae_1_reffer;
            $totalReefer    = floatval($row['REEFER 20"'] ?? 0) + floatval($row['REEFER 40"'] ?? 0);

            $allAeZero     = collect($loads)->every(fn($v) => !is_numeric($v) || floatval($v) == 0);
            $reeferExceeds = count($activeLoads) > 1 && $blAe1Reffer !== null && $totalReefer < $blAe1Reffer;
            $rowAnomalous  = $allAeZero || $reeferExceeds;

            $me_without_manuev_Condition = is_numeric($meHsd) && $meHsd != 0 && is_numeric($maneuvering) && $maneuvering == 0;
            $excess_ae_par_dur_Condition = is_numeric($ae_par_dur) && is_numeric($maneuvering) && is_numeric($crane_dur)
                                            && ($ae_par_dur - $maneuvering - $crane_dur > 3);
            $isLoadCondition             = count($activeLoads) > 1 && is_numeric($ae_par_dur) && $ae_par_dur == 0;
            $isLoadDifferentCondition    = count($activeLoads) > 1 && count(array_unique($activeLoads)) > 1;
            $isSingleLoadCondition       = count($activeLoads) === 1 && is_numeric($ae_par_dur) && $ae_par_dur != 0;
            $is24HoursAePararelCondition = is_numeric($ae_par_dur) && $ae_par_dur == 24;

            $aeLoadCols      = ['LOAD A/E 1 (KW)', 'LOAD A/E 2 (KW)', 'LOAD A/E 3 (KW)', 'LOAD A/E 4 (KW)', 'AE PARAREL DURATION', 'REEFER 20"', 'REEFER 40"'];
            $highlightAeLoad = $isLoadCondition || $isLoadDifferentCondition || $isSingleLoadCondition || $is24HoursAePararelCondition;

            $newRow = [];
            foreach ($row as $key => $value) {
                $class = '';

                if (in_array($key, $greenColumns)) {
                    $class .= ' bg-green-200 font-semibold';
                }
                if (in_array($key, $analysisColumns) && is_numeric($value) && $value < 0) {
                    $class .= ' bg-red-200 font-semibold';
                }
                if ($me_without_manuev_Condition && in_array($key, ['M/E HSD', 'MANEUVERING TIME (HOURS)'])) {
                    $class .= ' bg-yellow-200 font-semibold';
                }
                if ($excess_ae_par_dur_Condition && in_array($key, ['AE PARAREL DURATION', 'MANEUVERING TIME (HOURS)', 'CRANE DURATION'])) {
                    $class .= ' bg-blue-200 font-semibold';
                }
                if ($highlightAeLoad && in_array($key, $aeLoadCols)) {
                    $class .= ' bg-yellow-200 font-semibold';
                }

                $newRow[$key] = ['value' => $value, 'class' => $class];
            }

            $newRow['_row_class'] = ['value' => $rowAnomalous ? 'bg-red-200' : '', 'class' => ''];

            return $newRow;
        }, $sea_data);

        $density = floatval($request->input('density', 950));

        if (False) {
            $dinamisRaw  = $this->getMockDinamisSeaData();
            $dinamisNorm = array_map(fn($r) => $this->reorderDinamisReport($r), $dinamisRaw['data'] ?? []);
        } else {
            $dinamisNorm = array_map(fn($r) => $this->reorderDinamisReport($r), $rawSeaReports);
        }
        $dinamisNorm  = deduplicateByVesselId($dinamisNorm);
        $dinamisKeyed = $this->getDinamisSeaData($dinamisNorm, $density);

        $fetchedVesselIds = array_keys($dinamisKeyed);
        if (!empty($fetchedVesselIds)) {
            \App\Models\FuelBaseline::whereIn('vessel_id', $fetchedVesselIds)
                ->update(['density' => $density]);
        }

        foreach ($colored_sea as &$row) {
            $vid      = $row['Vessel ID']['value'] ?? '';
            $dinamis  = $dinamisKeyed[$vid] ?? [];
            $blMeDay  = $row['BL M/E Static (L/Day)']['value'] ?? 0;
            $steamTime   = floatval($row['STEAM TIME (HOUR : MINUTE)']['value'] ?? 0);
            $maneuvTime  = floatval($row['MANEUVERING TIME (HOURS)']['value'] ?? 0);
            $idealStatic = ($blMeDay > 0 && ($steamTime + $maneuvTime) > 0)
                ? round($blMeDay * ($steamTime + $maneuvTime) / 24, 2)
                : '';
            $sfocKw = $dinamis['SFOC_KW'] ?? 0;
            $dayaMeVal = floatval($dinamis['DAYA ME (KW)'] ?? 0);
            $idealDynamic = $sfocKw > 0
                ? round($sfocKw * ($steamTime + $maneuvTime) * $dayaMeVal, 2)
                : '';

            $row['Ideal Consumption Static (L/Day)']   = ['value' => $idealStatic, 'class' => ''];
            $row['DAYA ME (KW)']                       = ['value' => $dinamis['DAYA ME (KW)'] ?? '', 'class' => ''];
            $row['Ideal Consumption Dynamic (L)']      = ['value' => $idealDynamic, 'class' => ''];
            $row['Konsumsi M/E MFO Aktual']            = ['value' => $dinamis['Konsumsi M/E MFO Aktual'] ?? '', 'class' => ''];
            $row['Gap']                                = ['value' => $dinamis['Gap'] ?? '', 'class' => (($dinamis['Gap'] ?? '') === 'Tidak ada data kurva') ? 'bg-yellow-200 font-semibold' : ''];
            $row['Error']                              = ['value' => $dinamis['Error'] ?? '', 'class' => ''];
        }
        unset($row);

        $headers_port = array_filter(array_keys($colored_port[0] ?? []), fn($k) => $k !== '_row_class');
        $headers_sea  = array_filter(array_keys($colored_sea[0] ?? []), fn($k) => $k !== '_row_class');

        session(['port_anomaly' => $colored_port, 'sea_anomaly' => $colored_sea, 'port_sea_data' => $port_sea_data]);

        return view('pages.consumption', [
            'headers_port' => $headers_port,
            'report14' => $colored_port,
            'headers_sea' => $headers_sea,
            'report16' => $colored_sea,
            'port_sea_header' => array_keys($port_sea_data[0] ?? []),
            'port_sea_data' => $port_sea_data,
            'density' => $density,
            'isDinamis' => false,
        ]);
    }

    private function loadVesselEmails()
    {
        $emailFilePath = storage_path('app/email.xlsx');
        $spreadsheetEmail = IOFactory::load($emailFilePath);
        $sheetEmail = $spreadsheetEmail->getActiveSheet();
        $emailData = $sheetEmail->toArray(null, true, true, true);

        $vesselEmails = [];
        foreach (array_slice($emailData, 1) as $row) {
            $vessel = strtoupper(trim($row['A']));
            if ($vessel) {
                $vesselEmails[$vessel] = [
                    'Email Kapal' => trim($row['B'] ?? ''),
                    'Email SS/SI' => trim($row['C'] ?? ''),
                    'Email MT'    => trim($row['D'] ?? ''),
                    'Email MN'    => trim($row['E'] ?? ''),
                    'Email DGM'   => trim($row['F'] ?? ''),
                    'Email GM'    => trim($row['G'] ?? ''),
                    'Email DPA'   => trim($row['H'] ?? ''),
                    'Email SSB01' => trim($row['I'] ?? ''),
                    'OIL MGT'     => trim($row['J'] ?? '')
                ];
            }
        }

        return $vesselEmails;
    }

    private function processEmailRow($row, $sheetName, $vesselEmails)
    {
        $primaryRole = 'Email Kapal';
        $ccRoles = ['Email SS/SI', 'Email MT', 'Email MN', 'Email DGM', 'Email GM', 'Email DPA', 'Email SSB01', 'OIL MGT'];

        $vesselName = strtoupper($row['Vessel ID']['value'] ?? 'UNKNOWN');
        $date_val = $row['tanggal']['value'] ?? '';
        $pos_val = $row['POSITION']['value'] ?? '';
        $dep_val = $row['DEPARTURE PORT']['value'] ?? '';
        $des_val = $row['DESTINATION']['value'] ?? '';

        $me_hsd_val = floatval($row['M/E HSD']['value'] ?? 0);
        $manuvering_time_val = floatval($row['MANEUVERING TIME (HOURS)']['value'] ?? 0);
        $ae_pararel_val = floatval($row['AE PARAREL DURATION']['value'] ?? 0);
        $crane_duration_val = floatval($row['CRANE DURATION']['value'] ?? 0);

        $loads = [
            'LOAD A/E 1 (KW)' => floatval($row['LOAD A/E 1 (KW)']['value'] ?? 0),
            'LOAD A/E 2 (KW)' => floatval($row['LOAD A/E 2 (KW)']['value'] ?? 0),
            'LOAD A/E 3 (KW)' => floatval($row['LOAD A/E 3 (KW)']['value'] ?? 0),
            'LOAD A/E 4 (KW)' => floatval($row['LOAD A/E 4 (KW)']['value'] ?? 0),
        ];

        $refer20 = floatval($row['REEFER 20"']['value'] ?? 0);
        $refer40 = floatval($row['REEFER 40"']['value'] ?? 0);

        $activeLoads = array_filter($loads, fn($v) => $v > 0);

        $isMultipleLoadNoPararel = count($activeLoads) > 1 && $ae_pararel_val == 0;
        $isLoadDifferent         = count(array_unique($activeLoads)) > 1 && count($activeLoads) > 1;
        $isSingleLoadWithPararel = count($activeLoads) === 1 && $ae_pararel_val != 0;
        $is24HoursPararel        = $ae_pararel_val == 24;

        $htmlBody = "Dear Capt/KKM <br><br>";
        $htmlBody .= "Terlampir di noon report<br>";
        $htmlBody .= "<b>{$date_val} {$sheetName}:</b><br>";

        if ($sheetName === 'At PORT') {
            $htmlBody .= "<br>Posisi: {$pos_val}<br>";
        }

        if ($sheetName === 'At SEA') {
            $htmlBody .= "<br>Posisi perjalanan dari {$dep_val} ke {$des_val}<br>";
        }

        if ($me_hsd_val != 0 && $manuvering_time_val == 0) {
            $htmlBody .= "<br>Terdapat pemakaian <b>ME HSD sebanyak {$me_hsd_val} liter tanpa adanya manuvering</b><br>";
        }

        if (($ae_pararel_val - $crane_duration_val - $manuvering_time_val) > 3) {
            $htmlBody .= "<br>Terdapat durasi pemakaian <b>AE Pararel berlebih selama {$ae_pararel_val} jam</b> yang disertai <b>pemakaian Crane selama {$crane_duration_val} jam</b> dan <b>durasi manuvering selama {$manuvering_time_val} jam</b>.<br>";
        }

        if ($isMultipleLoadNoPararel || $isLoadDifferent || $isSingleLoadWithPararel || $is24HoursPararel) {
            if ($isMultipleLoadNoPararel) {
                $htmlBody .= "<br>Terdapat lebih dari 1 LOAD A/E aktif tetapi <b>tidak ada durasi A/E Pararel</b>.<br>";
                $htmlBody .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; text-align: center; margin-top: 20px;">';
                $htmlBody .= '<thead><tr>
                    <th>LOAD A/E 1 (KW)</th>
                    <th>LOAD A/E 2 (KW)</th>
                    <th>LOAD A/E 3 (KW)</th>
                    <th>LOAD A/E 4 (KW)</th>
                    <th>AE PARAREL DURATION</th>
                    <th>REEFER 20"</th>
                    <th>REEFER 40"</th>
                </tr></thead><tbody>';

                $htmlBody .= "<tr>
                    <td>" . number_format($loads['LOAD A/E 1 (KW)'], 2) . "</td>
                    <td>" . number_format($loads['LOAD A/E 2 (KW)'], 2) . "</td>
                    <td>" . number_format($loads['LOAD A/E 3 (KW)'], 2) . "</td>
                    <td>" . number_format($loads['LOAD A/E 4 (KW)'], 2) . "</td>
                    <td>" . number_format($ae_pararel_val, 2) . "</td>
                    <td>" . number_format($refer20, 2) . "</td>
                    <td>" . number_format($refer40, 2) . "</td>
                </tr>";

                $htmlBody .= '</tbody></table>';
            }

            if ($isLoadDifferent) {
                $htmlBody .= "<br>Terdapat lebih dari 1 LOAD A/E aktif dengan <b>nilai berbeda</b>.<br>";
                $htmlBody .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; text-align: center; margin-top: 20px;">';
                $htmlBody .= '<thead><tr>
                    <th>LOAD A/E 1 (KW)</th>
                    <th>LOAD A/E 2 (KW)</th>
                    <th>LOAD A/E 3 (KW)</th>
                    <th>LOAD A/E 4 (KW)</th>
                    <th>AE PARAREL DURATION</th>
                    <th>REEFER 20"</th>
                    <th>REEFER 40"</th>
                </tr></thead><tbody>';

                $htmlBody .= "<tr>
                    <td>" . number_format($loads['LOAD A/E 1 (KW)'], 2) . "</td>
                    <td>" . number_format($loads['LOAD A/E 2 (KW)'], 2) . "</td>
                    <td>" . number_format($loads['LOAD A/E 3 (KW)'], 2) . "</td>
                    <td>" . number_format($loads['LOAD A/E 4 (KW)'], 2) . "</td>
                    <td>" . number_format($ae_pararel_val, 2) . "</td>
                    <td>" . number_format($refer20, 2) . "</td>
                    <td>" . number_format($refer40, 2) . "</td>
                </tr>";

                $htmlBody .= '</tbody></table>';
            }

            if ($isSingleLoadWithPararel) {
                $htmlBody .= "<br>Terdapat hanya 1 LOAD A/E aktif tetapi <b>Durasi AE PARAREL ≠ 0</b>.<br>";
                $htmlBody .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; text-align: center; margin-top: 20px;">';
                $htmlBody .= '<thead><tr>
                    <th>LOAD A/E 1 (KW)</th>
                    <th>LOAD A/E 2 (KW)</th>
                    <th>LOAD A/E 3 (KW)</th>
                    <th>LOAD A/E 4 (KW)</th>
                    <th>AE PARAREL DURATION</th>
                    <th>REEFER 20"</th>
                    <th>REEFER 40"</th>
                </tr></thead><tbody>';

                $htmlBody .= "<tr>
                    <td>" . number_format($loads['LOAD A/E 1 (KW)'], 2) . "</td>
                    <td>" . number_format($loads['LOAD A/E 2 (KW)'], 2) . "</td>
                    <td>" . number_format($loads['LOAD A/E 3 (KW)'], 2) . "</td>
                    <td>" . number_format($loads['LOAD A/E 4 (KW)'], 2) . "</td>
                    <td>" . number_format($ae_pararel_val, 2) . "</td>
                    <td>" . number_format($refer20, 2) . "</td>
                    <td>" . number_format($refer40, 2) . "</td>
                </tr>";

                $htmlBody .= '</tbody></table>';
            }

            if ($is24HoursPararel) {
                $htmlBody .= "<br>Terdapat <b>Durasi AE PARAREL selama 24 jam</b>.<br>";
                $htmlBody .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; text-align: center; margin-top: 20px;">';
                $htmlBody .= '<thead><tr>
                    <th>LOAD A/E 1 (KW)</th>
                    <th>LOAD A/E 2 (KW)</th>
                    <th>LOAD A/E 3 (KW)</th>
                    <th>LOAD A/E 4 (KW)</th>
                    <th>AE PARAREL DURATION</th>
                    <th>REEFER 20"</th>
                    <th>REEFER 40"</th>
                </tr></thead><tbody>';

                $htmlBody .= "<tr>
                    <td>" . number_format($loads['LOAD A/E 1 (KW)'], 2) . "</td>
                    <td>" . number_format($loads['LOAD A/E 2 (KW)'], 2) . "</td>
                    <td>" . number_format($loads['LOAD A/E 3 (KW)'], 2) . "</td>
                    <td>" . number_format($loads['LOAD A/E 4 (KW)'], 2) . "</td>
                    <td>" . number_format($ae_pararel_val, 2) . "</td>
                    <td>" . number_format($refer20, 2) . "</td>
                    <td>" . number_format($refer40, 2) . "</td>
                </tr>";

                $htmlBody .= '</tbody></table>';
            }
        }

        // Cek nilai negatif
        $negativeEntries = [];
        foreach ($row as $key => $cell) {
            if (is_array($cell) && isset($cell['value']) && floatval($cell['value']) < 0) {
                $negativeEntries[] = "{$key}: {$cell['value']}";
            }
        }

        foreach ($negativeEntries as $entry) {
            [$columnName, $value] = explode(': ', $entry);
            switch (trim($columnName)) {
                case 'SELISIH ME Maneuvering':
                    $htmlBody .= "<br>Terdapat <b>pemakaian ME berlebih untuk manuevering</b>.<br><br>";
                    $htmlBody .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; text-align: center;">';
                    $htmlBody .= '<thead><tr>
                        <th>ME HSD</th>
                        <th>Manuevering Time (Hours)</th>
                        <th>BL ME</th>
                        <th>ME Maneuvering Consumption (L/H)</th>
                        <th>Selisih ME Maneuvering</th>
                    </tr></thead><tbody>';

                    $me_hsd_val = $row['M/E HSD']['value'] ?? 0;
                    $manuvering_time_val = $row['MANEUVERING TIME (HOURS)']['value'] ?? 0;
                    $bl_me_val = $row['BL M/E Static (L/Day)']['value'] ?? 0;
                    $me_maneuv_val = $row['ME Maneuvering Cons. (L/H)']['value'] ?? 0;
                    $selisih_me_maneuv_val = $row['SELISIH ME Maneuvering']['value'] ?? 0;

                    $htmlBody .= "<tr>
                        <td>" . number_format($me_hsd_val, 2) . "</td>
                        <td>" . number_format($manuvering_time_val, 2) . "</td>
                        <td>" . number_format($bl_me_val, 2) . "</td>
                        <td>" . number_format($me_maneuv_val, 2) . "</td>
                        <td>" . "<b>" . number_format($selisih_me_maneuv_val, 2) . "</b>" . "</td>
                    </tr>";

                    $htmlBody .= '</tbody></table>';
                    break;

                case 'EXCESS AE':
                    $htmlBody .= "<br>Terdapat <b>pemakaian AE berlebih</b>.<br>";
                    $htmlBody .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; text-align: center; margin-top: 20px;">';
                    $htmlBody .= '<thead><tr>
                        <th>A/E MFO</th>
                        <th>A/E HSD</th>
                        <th>GENSET CONSUMPTION - HSD</th>
                        <th>BL AE (L/DAY)</th>
                        <th>AE (L/DAY)</th>
                        <th>Excess AE</th>
                    </tr></thead><tbody>';

                    $ae_mfo_val = $row['A/E MFO']['value'] ?? 0;
                    $ae_hsd_val = $row['A/E HSD']['value'] ?? 0;
                    $genset_hsd_val = $row['GENSET CONSUMPTION - HSD']['value'] ?? 0;
                    $bl_ae_val = $row['BL A/E (L/Day)']['value'] ?? 0;
                    $total_ae = $row['AE Consumption']['value'] ?? 0;
                    $excess_ae_val = $row['EXCESS AE']['value'] ?? 0;

                    $htmlBody .= "<tr>
                        <td>" . number_format($ae_mfo_val, 2) . "</td>
                        <td>" . number_format($ae_hsd_val, 2) . "</td>
                        <td>" . number_format($genset_hsd_val, 2) . "</td>
                        <td>" . number_format($bl_ae_val, 2) . "</td>
                        <td>" . number_format($total_ae, 2) . "</td>
                        <td>" . "<b>" . number_format($excess_ae_val, 2) . "</b>" . "</td>
                    </tr>";

                    $htmlBody .= '</tbody></table>';
                    break;

                case 'EXCESS ME L/NM (%)':
                    $htmlBody .= "<br>Terdapat <b>pemakaian ME MFO berlebih</b>.<br>";
                    $htmlBody .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; text-align: center; margin-top: 20px;">';
                    $htmlBody .= '<thead><tr>
                        <th>Steam Distance (Miles)</th>
                        <th>Steam Time (Hour)</th>
                        <th>M/E MFO</th>
                        <th>BL L/NM</th>
                        <th>L/NM</th>
                        <th>Excess ME L/NM (%)</th>
                    </tr></thead><tbody>';

                    $steam_distance_val = $row['STEAM. DIST.']['value'] ?? 0;
                    $steam_time_val = $row['STEAM TIME (HOUR : MINUTE)']['value'] ?? 0;
                    $me_mfo_val = $row['M/E MFO']['value'] ?? 0;
                    $bl_ln_val = $row['BL L/NM']['value'] ?? 0;
                    $lnm_val = $row['L/NM']['value'] ?? 0;
                    $excess_me_mfo_lnm_val = $row['EXCESS ME L/NM (%)']['value'] ?? 0;

                    $htmlBody .= "<tr>
                        <td>" . number_format($steam_distance_val, 2) . "</td>
                        <td>" . number_format($steam_time_val, 2) . "</td>
                        <td>" . number_format($me_mfo_val, 2) . "</td>
                        <td>" . number_format($bl_ln_val, 2) . "</td>
                        <td>" . number_format($lnm_val, 2) . "</td>
                        <td>" . "<b>" . number_format($excess_me_mfo_lnm_val, 2) . "</b>" . "</td>
                    </tr>";

                    $htmlBody .= '</tbody></table>';
                    break;
            }
        }

        $htmlBody .= "<br>Mohon dijelaskan terkait detail laporan diatas.<br><br>";
        $htmlBody .= "Atas perhatian dan kerjasama Saudara, saya mengucapkan terima kasih.<br><br>";
        $htmlBody .= "Rgrds<br>";
        $htmlBody .= "Tim Bunker<br>";

        $toEmail = $vesselEmails[$vesselName][$primaryRole] ?? 'marulihtgl12@gmail.com';
        $ccEmails = [];

        foreach ($ccRoles as $role) {
            $email = $vesselEmails[$vesselName][$role] ?? null;
            if ($email) {
                $ccEmails[] = $email;
            }
        }

        Mail::html($htmlBody, function ($message) use ($sheetName, $vesselName, $toEmail, $ccEmails) {
            $message->to($toEmail)
                    ->cc($ccEmails)
                    ->subject("Permohonan penjelasan laporan {$vesselName} {$sheetName}");
        });
    }

    public function sendEmail(Request $request)
    {
        $selectedPort = $request->input('selected_rows_port', []);
        $selectedSea = $request->input('selected_rows_sea', []);

        $reportPort = session('port_anomaly', []);
        $reportSea = session('sea_anomaly', []);
        $headersPort = session('headers_port', []);
        $headersSea = session('headers_sea', []);

        $selectedDataPort = collect($reportPort)->only($selectedPort)->values()->all();
        $selectedDataSea = collect($reportSea)->only($selectedSea)->values()->all();

        Log::info('selectedDataSea', [
            $selectedDataSea
        ]);

        $vesselEmails = $this->loadVesselEmails();

        foreach ($selectedDataPort as $row) {
            $this->processEmailRow($row, 'At PORT', $vesselEmails);
        }

        foreach ($selectedDataSea as $row) {
            $this->processEmailRow($row, 'At SEA', $vesselEmails);
        }

        return redirect('pages.consumption')
            ->with('success_email', 'All e-mails sent successfully.');
    }

    private function getDinamisSeaData(array $sea_data, float $density): array
    {
        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/Titik Ori Grafik.xlsx'))->getActiveSheet();
        $raw   = $sheet->toArray(null, true, true, true);

        $titik = [];
        $vessels_titik = [];
        foreach ($raw[1] as $col => $header) {
            $parts = explode(' ', trim($header));
            if (count($parts) == 2) {
                [$vessel, $axis] = $parts;
                $values = [];
                foreach (array_slice($raw, 1) as $row) {
                    if (isset($row[$col]) && $row[$col] !== null) $values[] = $row[$col];
                }
                if (!in_array($vessel, $vessels_titik)) $vessels_titik[] = strtoupper($vessel);
                $titik[$vessel][$axis] = $values;
            }
        }

        $konstan_data = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/SFOC Konstan.xlsx'))
            ->getActiveSheet()->toArray(null, true, true, true);
        $konstan_kurva = [];
        $vessels_konstan = [];
        foreach (array_slice($konstan_data, 1) as $row) {
            $vessel = strtoupper(trim($row['A']));
            if ($vessel && !in_array($vessel, $vessels_konstan)) {
                $konstan_kurva[$vessel] = ['SFOC' => trim($row['B'] ?? ''), 'SATUAN' => trim($row['C'] ?? '')];
                $vessels_konstan[] = $vessel;
            }
        }

        $power_data = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/power.xlsx'))
            ->getActiveSheet()->toArray(null, true, true, true);
        $power = [];
        foreach (array_slice($power_data, 1) as $row) {
            $vessel = strtoupper(trim($row['A']));
            if ($vessel) $power[$vessel] = ['KW' => floatval(trim($row['C'] ?? '0'))];
        }

        $all_vessels = array_unique(array_merge($vessels_titik, $vessels_konstan));

        $result = [];
        foreach ($sea_data as $report) {
            $vesselId        = strtoupper(trim($report['Vessel ID'] ?? ''));
            $powerKw         = $power[$vesselId]['KW'] ?? 0;
            $dayaMe          = floatval($report['DAYA ME (KW)'] ?? 0);
            $konsumsi_aktual = floatval($report['Konsumsi M/E MFO Aktual'] ?? 0);

            $perhitungan = '';
            $gap         = '';
            $error       = '';

            $sfoc_kw = 0;

            if ($dayaMe <= $powerKw && $powerKw > 0 && $konsumsi_aktual > 0) {
                $k = $this->hitungKonsumsi($report, $titik, $density, $konstan_kurva, $all_vessels);
                if (is_numeric($k)) {
                    $steamTime   = floatval($report['STEAM TIME (HOUR : MINUTE)'] ?? 0);
                    $sfoc_kw     = ($dayaMe > 0 && $steamTime > 0) ? $k / ($dayaMe * $steamTime) : 0;
                    $perhitungan = $k;
                    $gap         = $k - $konsumsi_aktual;
                    $error       = $k != 0 ? round((($k - $konsumsi_aktual) / $k) * 100, 2) . ' %' : '';
                } else {
                    $gap = 'Tidak ada data kurva';
                }
            }

            $result[$vesselId] = [
                'DAYA ME (KW)'                 => $dayaMe,
                'Konsumsi M/E MFO Aktual'      => $konsumsi_aktual,
                'Konsumsi M/E MFO Perhitungan' => $perhitungan,
                'SFOC_KW'                      => $sfoc_kw,
                'BL M/E Dynamic (L/Day)'       => $sfoc_kw > 0 ? round($sfoc_kw * $dayaMe * 24, 2) : '',
                'Gap'                          => $gap,
                'Error'                        => $error,
            ];
        }

        return $result;
    }

    private function getMockPortData(): array
    {
        return [
            'data' => [
                ['vesselid'=>'AKA','tanggal'=>'2026-06-13 11:44:00','pos'=>'KAPAL SANDAR DI DERMAGA PANTOLOAN-PALU','departure'=>null,'destination'=>null,'steam_dist'=>null,'steam_time'=>null,'ship_speed'=>null,'prop_slip'=>null,'me_rpm'=>null,'me_mfo'=>0,'me_hsd'=>0,'ae_mfo'=>0,'ae_hsd'=>53,'duration_manuev'=>0,'boiler_hsd'=>null,'boiler_mfo'=>null,'genset_consum_hsd'=>0,'emg'=>null,'total_crane'=>0,'crane_duration'=>0,'load_ae_1'=>35,'load_ae_2'=>0,'load_ae_3'=>0,'load_ae_4'=>0,'ae_pararel_duration'=>0,'reefer20'=>0,'reefer40'=>0,'me_manuev_consum'=>0],
                ['vesselid'=>'ASR','tanggal'=>'2026-06-13 12:08:00','pos'=>'BERLALU MUARA JAWA','departure'=>null,'destination'=>null,'steam_dist'=>null,'steam_time'=>null,'ship_speed'=>null,'prop_slip'=>null,'me_rpm'=>null,'me_mfo'=>0,'me_hsd'=>2420,'ae_mfo'=>0,'ae_hsd'=>1303,'duration_manuev'=>7.1,'boiler_hsd'=>null,'boiler_mfo'=>null,'genset_consum_hsd'=>0,'emg'=>null,'total_crane'=>0,'crane_duration'=>0,'load_ae_1'=>0,'load_ae_2'=>140,'load_ae_3'=>140,'load_ae_4'=>0,'ae_pararel_duration'=>8,'reefer20'=>1,'reefer40'=>0,'me_manuev_consum'=>340.85],
                ['vesselid'=>'HAP','tanggal'=>'2026-06-13 10:53:00','pos'=>'sandar dermaga timika','departure'=>null,'destination'=>null,'steam_dist'=>null,'steam_time'=>null,'ship_speed'=>null,'prop_slip'=>null,'me_rpm'=>null,'me_mfo'=>0,'me_hsd'=>0,'ae_mfo'=>0,'ae_hsd'=>1870,'duration_manuev'=>0,'boiler_hsd'=>null,'boiler_mfo'=>null,'genset_consum_hsd'=>0,'emg'=>null,'total_crane'=>0,'crane_duration'=>0,'load_ae_1'=>133,'load_ae_2'=>133,'load_ae_3'=>0,'load_ae_4'=>0,'ae_pararel_duration'=>24,'reefer20'=>17,'reefer40'=>0,'me_manuev_consum'=>0],
                ['vesselid'=>'MHI','tanggal'=>'2026-06-13 12:17:00','pos'=>'2 NM TENGGARA OB MOROSI','departure'=>null,'destination'=>null,'steam_dist'=>null,'steam_time'=>null,'ship_speed'=>null,'prop_slip'=>null,'me_rpm'=>null,'me_mfo'=>0,'me_hsd'=>0,'ae_mfo'=>4493,'ae_hsd'=>0,'duration_manuev'=>1.1,'boiler_hsd'=>null,'boiler_mfo'=>null,'genset_consum_hsd'=>0,'emg'=>null,'total_crane'=>2,'crane_duration'=>24,'load_ae_1'=>135,'load_ae_2'=>0,'load_ae_3'=>135,'load_ae_4'=>0,'ae_pararel_duration'=>24,'reefer20'=>0,'reefer40'=>0,'me_manuev_consum'=>0],
                ['vesselid'=>'PSM','tanggal'=>'2026-06-13 13:16:00','pos'=>'SANDAR SAMPIT','departure'=>null,'destination'=>null,'steam_dist'=>null,'steam_time'=>null,'ship_speed'=>null,'prop_slip'=>null,'me_rpm'=>null,'me_mfo'=>0,'me_hsd'=>1848,'ae_mfo'=>0,'ae_hsd'=>310,'duration_manuev'=>8.5,'boiler_hsd'=>null,'boiler_mfo'=>null,'genset_consum_hsd'=>340,'emg'=>null,'total_crane'=>0,'crane_duration'=>0,'load_ae_1'=>30,'load_ae_2'=>0,'load_ae_3'=>0,'load_ae_4'=>0,'ae_pararel_duration'=>0,'reefer20'=>0,'reefer40'=>0,'me_manuev_consum'=>217.41],
                ['vesselid'=>'OJA','tanggal'=>'2026-06-13 12:07:00','pos'=>'Dermaga Berlian Timur Surabaya','departure'=>null,'destination'=>null,'steam_dist'=>null,'steam_time'=>null,'ship_speed'=>null,'prop_slip'=>null,'me_rpm'=>null,'me_mfo'=>0,'me_hsd'=>0,'ae_mfo'=>0,'ae_hsd'=>1560,'duration_manuev'=>0,'boiler_hsd'=>null,'boiler_mfo'=>null,'genset_consum_hsd'=>0,'emg'=>null,'total_crane'=>1,'crane_duration'=>2,'load_ae_1'=>185,'load_ae_2'=>0,'load_ae_3'=>0,'load_ae_4'=>0,'ae_pararel_duration'=>2,'reefer20'=>2,'reefer40'=>0,'me_manuev_consum'=>0],
                ['vesselid'=>'VEI','tanggal'=>'2026-06-13 12:29:00','pos'=>'BERLABUHJANGKAR REDE TARAKAN','departure'=>null,'destination'=>null,'steam_dist'=>null,'steam_time'=>null,'ship_speed'=>null,'prop_slip'=>null,'me_rpm'=>null,'me_mfo'=>0,'me_hsd'=>0,'ae_mfo'=>0,'ae_hsd'=>165,'duration_manuev'=>0,'boiler_hsd'=>null,'boiler_mfo'=>null,'genset_consum_hsd'=>0,'emg'=>null,'total_crane'=>0,'crane_duration'=>0,'load_ae_1'=>180,'load_ae_2'=>0,'load_ae_3'=>0,'load_ae_4'=>0,'ae_pararel_duration'=>1,'reefer20'=>10,'reefer40'=>0,'me_manuev_consum'=>0],
                ['vesselid'=>'MAN','tanggal'=>'2026-06-13 13:24:00','pos'=>'SANDAR DERMAGA MAKASSAR','departure'=>null,'destination'=>null,'steam_dist'=>null,'steam_time'=>null,'ship_speed'=>null,'prop_slip'=>null,'me_rpm'=>null,'me_mfo'=>0,'me_hsd'=>3105,'ae_mfo'=>0,'ae_hsd'=>512,'duration_manuev'=>5.2,'boiler_hsd'=>null,'boiler_mfo'=>null,'genset_consum_hsd'=>980,'emg'=>null,'total_crane'=>0,'crane_duration'=>0,'load_ae_1'=>0,'load_ae_2'=>0,'load_ae_3'=>0,'load_ae_4'=>0,'ae_pararel_duration'=>5.8,'reefer20'=>0,'reefer40'=>1,'me_manuev_consum'=>574.20],
                ['vesselid'=>'BSA','tanggal'=>'2026-06-13 11:14:00','pos'=>'SANDAR DERMAGA BANJARMASIN','departure'=>null,'destination'=>null,'steam_dist'=>null,'steam_time'=>null,'ship_speed'=>null,'prop_slip'=>null,'me_rpm'=>null,'me_mfo'=>0,'me_hsd'=>0,'ae_mfo'=>3120,'ae_hsd'=>175,'duration_manuev'=>3.6,'boiler_hsd'=>null,'boiler_mfo'=>null,'genset_consum_hsd'=>1080,'emg'=>null,'total_crane'=>2,'crane_duration'=>14.5,'load_ae_1'=>190,'load_ae_2'=>190,'load_ae_3'=>0,'load_ae_4'=>0,'ae_pararel_duration'=>18,'reefer20'=>15,'reefer40'=>3,'me_manuev_consum'=>0],
                ['vesselid'=>'PAH','tanggal'=>'2026-06-13 11:48:00','pos'=>'SANDAR DERMAGA MERAK','departure'=>null,'destination'=>null,'steam_dist'=>null,'steam_time'=>null,'ship_speed'=>null,'prop_slip'=>null,'me_rpm'=>null,'me_mfo'=>0,'me_hsd'=>640,'ae_mfo'=>0,'ae_hsd'=>715,'duration_manuev'=>2.8,'boiler_hsd'=>null,'boiler_mfo'=>null,'genset_consum_hsd'=>0,'emg'=>null,'total_crane'=>0,'crane_duration'=>0,'load_ae_1'=>50,'load_ae_2'=>50,'load_ae_3'=>0,'load_ae_4'=>0,'ae_pararel_duration'=>3.5,'reefer20'=>0,'reefer40'=>0,'me_manuev_consum'=>201.10],
            ]
        ];
    }

    private function getMockSeaData(): array
    {
        return [
            'data' => [
                ['vesselid'=>'ANO','tanggal'=>'2026-06-13 11:00:00','pos'=>'AT SEA SURABAYA - MAKASSAR','departure'=>'IDSUB','destination'=>'IDMAK','steam_dist'=>423,'steam_time'=>26,'ship_speed'=>16.3,'prop_slip'=>3.2,'me_rpm'=>118,'me_mfo'=>4200,'me_hsd'=>0,'ae_mfo'=>0,'ae_hsd'=>980,'duration_manuev'=>0,'boiler_hsd'=>0,'boiler_mfo'=>0,'genset_consum_hsd'=>0,'emg'=>0,'total_crane'=>0,'crane_duration'=>0,'load_ae_1'=>210,'load_ae_2'=>210,'load_ae_3'=>0,'load_ae_4'=>0,'ae_pararel_duration'=>0,'reefer20'=>5,'reefer40'=>2,'me_manuev_consum'=>0],
                ['vesselid'=>'BIM','tanggal'=>'2026-06-13 11:30:00','pos'=>'AT SEA MAKASSAR - BITUNG','departure'=>'IDMAK','destination'=>'IDBTG','steam_dist'=>380,'steam_time'=>25,'ship_speed'=>15.2,'prop_slip'=>4.1,'me_rpm'=>115,'me_mfo'=>3850,'me_hsd'=>0,'ae_mfo'=>0,'ae_hsd'=>870,'duration_manuev'=>0,'boiler_hsd'=>0,'boiler_mfo'=>0,'genset_consum_hsd'=>0,'emg'=>0,'total_crane'=>0,'crane_duration'=>0,'load_ae_1'=>185,'load_ae_2'=>185,'load_ae_3'=>0,'load_ae_4'=>0,'ae_pararel_duration'=>0,'reefer20'=>3,'reefer40'=>1,'me_manuev_consum'=>0],
                ['vesselid'=>'CEN','tanggal'=>'2026-06-13 10:45:00','pos'=>'AT SEA JAKARTA - SURABAYA','departure'=>'IDJKT','destination'=>'IDSUB','steam_dist'=>290,'steam_time'=>24,'ship_speed'=>12.1,'prop_slip'=>5.5,'me_rpm'=>108,'me_mfo'=>5100,'me_hsd'=>0,'ae_mfo'=>0,'ae_hsd'=>1100,'duration_manuev'=>1.5,'boiler_hsd'=>0,'boiler_mfo'=>0,'genset_consum_hsd'=>0,'emg'=>0,'total_crane'=>0,'crane_duration'=>0,'load_ae_1'=>230,'load_ae_2'=>0,'load_ae_3'=>230,'load_ae_4'=>0,'ae_pararel_duration'=>1.5,'reefer20'=>8,'reefer40'=>3,'me_manuev_consum'=>420],
                ['vesselid'=>'TFL','tanggal'=>'2026-06-13 12:00:00','pos'=>'AT SEA KENDARI - MAKASSAR','departure'=>'IDKDI','destination'=>'IDMAK','steam_dist'=>310,'steam_time'=>25,'ship_speed'=>12.4,'prop_slip'=>3.8,'me_rpm'=>112,'me_mfo'=>3200,'me_hsd'=>0,'ae_mfo'=>0,'ae_hsd'=>750,'duration_manuev'=>0,'boiler_hsd'=>0,'boiler_mfo'=>0,'genset_consum_hsd'=>0,'emg'=>0,'total_crane'=>0,'crane_duration'=>0,'load_ae_1'=>160,'load_ae_2'=>160,'load_ae_3'=>0,'load_ae_4'=>0,'ae_pararel_duration'=>0,'reefer20'=>0,'reefer40'=>0,'me_manuev_consum'=>0],
                ['vesselid'=>'OSI','tanggal'=>'2026-06-13 09:30:00','pos'=>'AT SEA TERNATE - BITUNG','departure'=>'IDTTE','destination'=>'IDBTG','steam_dist'=>195,'steam_time'=>24,'ship_speed'=>8.1,'prop_slip'=>6.2,'me_rpm'=>102,'me_mfo'=>6800,'me_hsd'=>0,'ae_mfo'=>1200,'ae_hsd'=>300,'duration_manuev'=>2,'boiler_hsd'=>0,'boiler_mfo'=>0,'genset_consum_hsd'=>1300,'emg'=>0,'total_crane'=>0,'crane_duration'=>0,'load_ae_1'=>200,'load_ae_2'=>200,'load_ae_3'=>0,'load_ae_4'=>0,'ae_pararel_duration'=>2,'reefer20'=>10,'reefer40'=>4,'me_manuev_consum'=>380],
                ['vesselid'=>'PWE','tanggal'=>'2026-06-13 11:15:00','pos'=>'AT SEA JAKARTA - PONTIANAK','departure'=>'IDJKT','destination'=>'IDPNK','steam_dist'=>520,'steam_time'=>36,'ship_speed'=>14.4,'prop_slip'=>2.9,'me_rpm'=>116,'me_mfo'=>4500,'me_hsd'=>0,'ae_mfo'=>0,'ae_hsd'=>1200,'duration_manuev'=>0,'boiler_hsd'=>0,'boiler_mfo'=>0,'genset_consum_hsd'=>0,'emg'=>0,'total_crane'=>0,'crane_duration'=>0,'load_ae_1'=>190,'load_ae_2'=>0,'load_ae_3'=>0,'load_ae_4'=>0,'ae_pararel_duration'=>0,'reefer20'=>4,'reefer40'=>2,'me_manuev_consum'=>0],
                ['vesselid'=>'HSG','tanggal'=>'2026-06-13 08:00:00','pos'=>'AT SEA SURABAYA - BANJARMASIN','departure'=>'IDSUB','destination'=>'IDBPN','steam_dist'=>350,'steam_time'=>27,'ship_speed'=>13.0,'prop_slip'=>4.5,'me_rpm'=>110,'me_mfo'=>4900,'me_hsd'=>350,'ae_mfo'=>0,'ae_hsd'=>600,'duration_manuev'=>3,'boiler_hsd'=>0,'boiler_mfo'=>0,'genset_consum_hsd'=>0,'emg'=>0,'total_crane'=>0,'crane_duration'=>0,'load_ae_1'=>0,'load_ae_2'=>0,'load_ae_3'=>0,'load_ae_4'=>0,'ae_pararel_duration'=>3,'reefer20'=>0,'reefer40'=>1,'me_manuev_consum'=>510],
                ['vesselid'=>'LUZ','tanggal'=>'2026-06-13 10:00:00','pos'=>'AT SEA MAKASSAR - SURABAYA','departure'=>'IDMAK','destination'=>'IDSUB','steam_dist'=>410,'steam_time'=>28,'ship_speed'=>14.6,'prop_slip'=>3.3,'me_rpm'=>114,'me_mfo'=>3600,'me_hsd'=>0,'ae_mfo'=>0,'ae_hsd'=>950,'duration_manuev'=>1,'boiler_hsd'=>0,'boiler_mfo'=>0,'genset_consum_hsd'=>0,'emg'=>0,'total_crane'=>0,'crane_duration'=>0,'load_ae_1'=>200,'load_ae_2'=>0,'load_ae_3'=>0,'load_ae_4'=>0,'ae_pararel_duration'=>1,'reefer20'=>12,'reefer40'=>0,'me_manuev_consum'=>650],
                ['vesselid'=>'BKU','tanggal'=>'2026-06-13 09:00:00','pos'=>'AT SEA BANJARMASIN - SURABAYA','departure'=>'IDBPN','destination'=>'IDSUB','steam_dist'=>330,'steam_time'=>25,'ship_speed'=>13.2,'prop_slip'=>3.0,'me_rpm'=>111,'me_mfo'=>2900,'me_hsd'=>0,'ae_mfo'=>0,'ae_hsd'=>680,'duration_manuev'=>0,'boiler_hsd'=>0,'boiler_mfo'=>0,'genset_consum_hsd'=>0,'emg'=>0,'total_crane'=>0,'crane_duration'=>0,'load_ae_1'=>155,'load_ae_2'=>155,'load_ae_3'=>0,'load_ae_4'=>0,'ae_pararel_duration'=>0,'reefer20'=>0,'reefer40'=>0,'me_manuev_consum'=>0],
                ['vesselid'=>'ASG','tanggal'=>'2026-06-13 07:30:00','pos'=>'AT SEA SURABAYA - MAKASSAR','departure'=>'IDSUB','destination'=>'IDMAK','steam_dist'=>440,'steam_time'=>30,'ship_speed'=>14.7,'prop_slip'=>2.7,'me_rpm'=>117,'me_mfo'=>5300,'me_hsd'=>0,'ae_mfo'=>0,'ae_hsd'=>1050,'duration_manuev'=>0,'boiler_hsd'=>0,'boiler_mfo'=>0,'genset_consum_hsd'=>0,'emg'=>0,'total_crane'=>0,'crane_duration'=>0,'load_ae_1'=>0,'load_ae_2'=>0,'load_ae_3'=>220,'load_ae_4'=>0,'ae_pararel_duration'=>0,'reefer20'=>0,'reefer40'=>0,'me_manuev_consum'=>0],
            ]
        ];
    }

    private function getMockDinamisSeaData(): array
    {
        return [
            'data' => [
                ['vesselid'=>'ANO','tanggal'=>'2026-06-13 11:00:00','pos'=>null,'departure'=>'IDSUB','destination'=>'IDMAK','steam_dist'=>423,'ship_speed'=>16.3,'steam_time'=>26,'daya_me_kw'=>770,'me_mfo'=>4200],
                ['vesselid'=>'BIM','tanggal'=>'2026-06-13 11:30:00','pos'=>null,'departure'=>'IDMAK','destination'=>'IDBTG','steam_dist'=>380,'ship_speed'=>15.2,'steam_time'=>25,'daya_me_kw'=>735,'me_mfo'=>3850],
                ['vesselid'=>'CEN','tanggal'=>'2026-06-13 10:45:00','pos'=>null,'departure'=>'IDJKT','destination'=>'IDSUB','steam_dist'=>290,'ship_speed'=>12.1,'steam_time'=>24,'daya_me_kw'=>1010,'me_mfo'=>5100],
                ['vesselid'=>'TFL','tanggal'=>'2026-06-13 12:00:00','pos'=>null,'departure'=>'IDKDI','destination'=>'IDMAK','steam_dist'=>310,'ship_speed'=>12.4,'steam_time'=>25,'daya_me_kw'=>610,'me_mfo'=>3200],
                ['vesselid'=>'OSI','tanggal'=>'2026-06-13 09:30:00','pos'=>null,'departure'=>'IDTTE','destination'=>'IDBTG','steam_dist'=>195,'ship_speed'=>8.1,'steam_time'=>24,'daya_me_kw'=>1350,'me_mfo'=>6800],
                ['vesselid'=>'PWE','tanggal'=>'2026-06-13 11:15:00','pos'=>null,'departure'=>'IDJKT','destination'=>'IDPNK','steam_dist'=>520,'ship_speed'=>14.4,'steam_time'=>36,'daya_me_kw'=>595,'me_mfo'=>4500],
                ['vesselid'=>'HSG','tanggal'=>'2026-06-13 08:00:00','pos'=>null,'departure'=>'IDSUB','destination'=>'IDBPN','steam_dist'=>350,'ship_speed'=>13.0,'steam_time'=>27,'daya_me_kw'=>865,'me_mfo'=>4900],
                ['vesselid'=>'LUZ','tanggal'=>'2026-06-13 10:00:00','pos'=>null,'departure'=>'IDMAK','destination'=>'IDSUB','steam_dist'=>410,'ship_speed'=>14.6,'steam_time'=>28,'daya_me_kw'=>612,'me_mfo'=>3600],
                ['vesselid'=>'BKU','tanggal'=>'2026-06-13 09:00:00','pos'=>null,'departure'=>'IDBPN','destination'=>'IDSUB','steam_dist'=>330,'ship_speed'=>13.2,'steam_time'=>25,'daya_me_kw'=>552,'me_mfo'=>2900],
                ['vesselid'=>'ASG','tanggal'=>'2026-06-13 07:30:00','pos'=>null,'departure'=>'IDSUB','destination'=>'IDMAK','steam_dist'=>440,'ship_speed'=>14.7,'steam_time'=>30,'daya_me_kw'=>840,'me_mfo'=>5300],
            ]
        ];
    }
}