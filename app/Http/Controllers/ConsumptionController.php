<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
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

        $steamDist = $this->normalizeNumeric($grouped['steam_dist'] ?? 0);
        $meMfo     = $this->normalizeNumeric($grouped['me_mfo'] ?? 0);
        $meHsd     = $this->normalizeNumeric($grouped['me_hsd'] ?? 0);

        $ordered = [
            'Vessel ID' => $grouped['vesselid'] ?? null,
            'tanggal'   => isset($grouped['tanggal'])
                ? (new DateTime($grouped['tanggal']))->format('d F Y H:i')
                : null,
            'POSITION'                          => $grouped['pos'] ?? null,
            'DEPARTURE PORT'                    => $grouped['departure'] ?? null,
            'DESTINATION'                       => $grouped['destination'] ?? null,
            'STEAM. DIST.'                      => $steamDist,
            'STEAM TIME (HOUR : MINUTE)'        => $grouped['steam_time'] ?? null,
            'SHIP SPEED'                        => $grouped['ship_speed'] ?? null,
            'PROPELLER SLIP'                    => $grouped['prop_slip'] ?? null,
            'ME RPM'                            => $grouped['me_rpm'] ?? null,
            'M/E MFO'                           => $meMfo,
            'M/E HSD'                           => $meHsd,
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
            'L/NM' => ($steamDist != 0 ? (($meMfo + $meHsd) / $steamDist) : 0),
            'EXCESS ME L/NM (%)' => (
                $steamDist != 0 && $bl_l_nm != 0
            ) ? (
                (($bl_l_nm - (($meMfo + $meHsd) / $steamDist)) / $bl_l_nm) * 100
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

    private function normalizeNumeric($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $str = trim((string) $value);
        $str = preg_replace('/[^0-9,\.\-]/', '', $str);

        if ($str === '' || $str === '-' || $str === '.' || $str === ',') {
            return 0.0;
        }

        $hasComma = strpos($str, ',') !== false;
        $hasDot   = strpos($str, '.') !== false;

        if ($hasComma && $hasDot) {
            $lastComma = strrpos($str, ',');
            $lastDot   = strrpos($str, '.');

            if ($lastComma > $lastDot) {
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            } else {
                $str = str_replace(',', '', $str);
            }
        } elseif ($hasComma) {
            $str = str_replace(',', '.', $str);
        }

        return (float) $str;
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
            'tanggal'   => isset($grouped['tanggal'])
                ? (new DateTime($grouped['tanggal']))->format('d F Y H:i')
                : null,
            'POSITION'                     => $grouped['pos'] ?? null,
            'DEPARTURE PORT'               => $grouped['departure'] ?? null,
            'DESTINATION'                  => $grouped['destination'] ?? null,
            'STEAM. DIST.'                 => $grouped['steam_dist'] ?? null,
            'SHIP SPEED'                   => $grouped['ship_speed'] ?? null,
            'STEAM TIME (HOUR : MINUTE)'   => $grouped['steam_time'] ?? null,
            'DAYA ME (KW)'                 => $grouped['daya_me_kw'] ?? null,
            'Konsumsi M/E MFO Aktual'      => $grouped['me_mfo'] ?? null,
            'Konsumsi M/E MFO Perhitungan' => '',
            'Gap'                          => '',
            'Error'                        => '',
            ];

        return $ordered;
    }

    public function show(Request $request)
    {
        set_time_limit(1200);

        if (!$request->filled('report_date')) {
            if (session()->has('consumption_report_date')) {
                return view('pages.consumption', [
                    'headers_port' => session('consumption_headers_port'),
                    'report14' => session('consumption_report14'),
                    'headers_sea' => session('consumption_headers_sea'),
                    'report16' => session('consumption_report16'),
                    'port_sea_header' => session('consumption_port_sea_header'),
                    'port_sea_data' => session('consumption_port_sea_data'),
                    'density' => session('consumption_density', 950),
                    'isDinamis' => false,
                ]);
            }

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

        $reportIds     = [14, 16];
        $allReports    = [];
        $rawSeaReports = [];
        $baselines     = \App\Models\FuelBaseline::all()->keyBy('vessel_id');

        foreach ($reportIds as $reportId) {
            $payload = $basePayload;
            $payload['report_id'] = (string)$reportId;

            if (false) {
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
        $sea_data  = $allReports[16] ?? [];

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
            $rowAnomalous  = ($allAeZero && $totalReefer > 0) || $reeferExceeds;

            $me_without_manuev_Condition    = is_numeric($meHsd) && $meHsd != 0 && is_numeric($maneuvering) && $maneuvering == 0;
            $excess_ae_par_dur_Condition    = is_numeric($ae_par_dur) && is_numeric($maneuvering) && is_numeric($crane_dur)
                                                && ($ae_par_dur - $maneuvering - $crane_dur > 3);
            $isLoadCondition                = count($activeLoads) > 1 && is_numeric($ae_par_dur) && $ae_par_dur == 0;
            $isLoadDifferentCondition       = count($activeLoads) > 1 && count(array_unique($activeLoads)) > 1;
            $isSingleLoadCondition          = count($activeLoads) === 1 && is_numeric($ae_par_dur) && $ae_par_dur != 0;
            $is24HoursAePararelCondition    = is_numeric($ae_par_dur) && $ae_par_dur == 24;

            $aeLoadCols      = ['LOAD A/E 1 (KW)', 'LOAD A/E 2 (KW)', 'LOAD A/E 3 (KW)', 'LOAD A/E 4 (KW)', 'AE PARAREL DURATION', 'REEFER 20"', 'REEFER 40"'];
            $highlightAeLoad = $isLoadCondition || $isLoadDifferentCondition || $isSingleLoadCondition || $is24HoursAePararelCondition;

            $anomalies = [
                'green' => [],
                'red' => [],
                'yellow' => [],
                'blue' => []
            ];
            
            if ($rowAnomalous) {
                if ($allAeZero && $totalReefer > 0) $anomalies['red'][] = "Semua beban A/E 0 tetapi terdapat Reefer aktif.";
                if ($reeferExceeds) $anomalies['red'][] = "Pararel Genset terdeteksi, tetapi jumlah Reefer kurang dari batas acuan.";
            }
            if ($me_without_manuev_Condition) $anomalies['yellow'][] = "Konsumsi M/E HSD ada tetapi Maneuvering Time 0.";
            if ($excess_ae_par_dur_Condition) $anomalies['blue'][] = "Terdapat sisa durasi pararel A/E > 3 jam yang tidak wajar.";
            if ($highlightAeLoad) $anomalies['yellow'][] = "Anomali beban A/E terdeteksi (pararel tanpa durasi, beban berbeda, single dengan durasi pararel, atau pararel 24 jam).";

            $newRow = [];
            foreach ($row as $key => $value) {
                $class = '';

                if (in_array($key, $greenColumns)) {
                    $class .= ' bg-green-200 font-semibold';
                    if (!in_array("Nilai referensi (baseline) standar kapal.", $anomalies['green'])) {
                        $anomalies['green'][] = "Nilai referensi (baseline) standar kapal.";
                    }
                }
                if (in_array($key, $analysisColumns) && is_numeric($value) && $value < 0) {
                    $class .= ' bg-red-200 font-semibold';
                    $anomalies['red'][] = "Terdapat nilai defisit/negatif pada kolom $key.";
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
            
            $anomaliesFiltered = array_filter($anomalies, fn($arr) => count($arr) > 0);
            $newRow['_anomalies'] = ['value' => $anomaliesFiltered, 'class' => ''];

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

            $anomalies = [
                'green' => [],
                'red' => [],
                'yellow' => [],
                'blue' => []
            ];
            
            if ($rowAnomalous) {
                if ($allAeZero && $totalReefer > 0) $anomalies['red'][] = "Semua beban A/E 0 tetapi terdapat Reefer aktif.";
                if ($reeferExceeds) $anomalies['red'][] = "Pararel Genset terdeteksi, tetapi jumlah Reefer kurang dari batas acuan.";
            }
            if ($me_without_manuev_Condition) $anomalies['yellow'][] = "Konsumsi M/E HSD ada tetapi Maneuvering Time 0.";
            if ($excess_ae_par_dur_Condition) $anomalies['blue'][] = "Terdapat sisa durasi pararel A/E > 3 jam yang tidak wajar.";
            if ($highlightAeLoad) $anomalies['yellow'][] = "Anomali beban A/E terdeteksi (pararel tanpa durasi, beban berbeda, single dengan durasi pararel, atau pararel 24 jam).";

            $newRow = [];
            foreach ($row as $key => $value) {
                $class = '';

                if (in_array($key, $greenColumns)) {
                    $class .= ' bg-green-200 font-semibold';
                    if (!in_array("Nilai referensi (baseline) standar kapal.", $anomalies['green'])) {
                        $anomalies['green'][] = "Nilai referensi (baseline) standar kapal.";
                    }
                }
                if (in_array($key, $analysisColumns) && is_numeric($value) && $value < 0) {
                    $class .= ' bg-red-200 font-semibold';
                    $anomalies['red'][] = "Terdapat nilai defisit/negatif pada kolom $key.";
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
            
            $anomaliesFiltered = array_filter($anomalies, fn($arr) => count($arr) > 0);
            $newRow['_anomalies'] = ['value' => $anomaliesFiltered, 'class' => ''];

            return $newRow;
        }, $sea_data);

        $density = floatval($request->input('density', 950));

        if (false) {
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
            
            $gapClass = '';
            if (($dinamis['Gap'] ?? '') === 'Tidak ada data kurva') {
                $gapClass = 'bg-yellow-200 font-semibold';
                if (!isset($row['_anomalies'])) {
                    $row['_anomalies'] = ['value' => [], 'class' => ''];
                }
                if (!isset($row['_anomalies']['value']['yellow'])) {
                    $row['_anomalies']['value']['yellow'] = [];
                }
                $row['_anomalies']['value']['yellow'][] = "Tidak ada data kurva untuk perhitungan dinamis.";
            }
            
            $row['Gap']                                = ['value' => $dinamis['Gap'] ?? '', 'class' => $gapClass];
            $row['Error']                              = ['value' => $dinamis['Error'] ?? '', 'class' => ''];
        }
        unset($row);

        $headers_port = array_filter(array_keys($colored_port[0] ?? []), fn($k) => $k !== '_row_class');
        $headers_sea  = array_filter(array_keys($colored_sea[0] ?? []), fn($k) => $k !== '_row_class');

        session(['port_anomaly' => $colored_port, 'sea_anomaly' => $colored_sea, 'port_sea_data' => $port_sea_data]);

        session([
            'consumption_report_date' => $reportDate,
            'consumption_density' => $density,
            'consumption_headers_port' => $headers_port,
            'consumption_report14' => $colored_port,
            'consumption_headers_sea' => $headers_sea,
            'consumption_report16' => $colored_sea,
            'consumption_port_sea_header' => array_keys($port_sea_data[0] ?? []),
            'consumption_port_sea_data' => $port_sea_data,
        ]);

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
}