<?php

namespace App\Http\Controllers;

set_time_limit(0);

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class BaselineController extends Controller
{
    function polyfitQuadratic(array $x, array $y) {
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

    function solveLinearSystem(array $A, array $B) {
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

    public function show(Request $request)
    { 
        $selectedVessel = $request->input('vessel') ?? 'AKA';
        $density = $request->input('density') ?? 950;

        $power_kw = $request->input('power_kw');
        $steam_time = $request->input('steam_time');

        ///// dropdown dan titik //////

        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/Titik Ori Grafik.xlsx'))->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);

        $headers = $data[1];

        $titik = [];

        foreach ($headers as $col => $header) {
            $parts = explode(' ', trim($header));
            if (count($parts) == 2) {
                [$vessel, $axis] = $parts;

                $values = [];
                foreach (array_slice($data, 1) as $row) {
                    if (isset($row[$col]) && $row[$col] !== null) {
                        $values[] = $row[$col];
                    }
                }

                $titik[$vessel][$axis] = $values;
            }
        }

        ////// sumbu kurva ///////

        $sumbu_sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/Sumbu Grafik.xlsx'))->getActiveSheet();
        $sumbu_data = $sumbu_sheet->toArray(null, true, true, true);

        $sumbu_kurva = [];
        foreach (array_slice($sumbu_data, 1) as $row) {
            $vessel = strtoupper(trim($row['A']));

            if ($vessel) {
                $sumbu_kurva[$vessel] = [
                    'Ori X Min' => trim($row['B'] ?? ''),
                    'Ori X Max' => trim($row['C'] ?? ''),
                    'Ori Y Min' => trim($row['D'] ?? ''),
                    'Ori Y Max' => trim($row['E'] ?? ''),
                ];
            }
        }

        $vesselMap = array_keys($sumbu_kurva);
        sort($vesselMap);

        $ori_sumbu_x_min_raw = $sumbu_kurva[$selectedVessel]['Ori X Min'] ?? null;
        $ori_sumbu_x_max_raw = $sumbu_kurva[$selectedVessel]['Ori X Max'] ?? null;

        $ori_sumbu_y_min_raw = $sumbu_kurva[$selectedVessel]['Ori Y Min'] ?? null;
        $ori_sumbu_y_max_raw = $sumbu_kurva[$selectedVessel]['Ori Y Max'] ?? null;

        ///// titik kurva //////

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

        if (in_array($selectedVessel, $kapal_kecil)){
            ///// X = kW //////////
            ///// Y = g/Kw/hr //////

            //////// convert titik ///////////

            $titik_x_ori = array_map(fn($x) => $x / 0.7457, $titik_x_ori_raw);
            $titik_y_ori = array_map(fn($y) => $y * 0.7457, $titik_y_ori_raw);

            $titik_x_convert = array_map(fn($x) => $x, $titik_x_ori_raw);
            $titik_y_convert = array_map(fn($y) => $y / $density, $titik_y_ori_raw);

            /////// convert sumbu ///////

            $ori_sumbu_x_min = $ori_sumbu_x_min_raw / 0.7457;
            $ori_sumbu_x_max = $ori_sumbu_x_max_raw / 0.7457;
            $ori_sumbu_y_min = $ori_sumbu_y_min_raw * 0.7457;
            $ori_sumbu_y_max = $ori_sumbu_y_max_raw * 0.7457;

            $convert_sumbu_x_min = $ori_sumbu_x_min_raw;
            $convert_sumbu_x_max = $ori_sumbu_x_max_raw;
            $convert_sumbu_y_min = $ori_sumbu_y_min_raw / $density;
            $convert_sumbu_y_max = $ori_sumbu_y_max_raw / $density;
        } 
        elseif (in_array($selectedVessel, $kapal_osi_oem)){
            ///// X = kW //////
            ///// Y = g/BHP/hr //////

            $titik_x_ori = array_map(fn($x) => $x / 0.7457, $titik_x_ori_raw);
            $titik_y_ori = $titik_y_ori_raw;

            $titik_x_convert = $titik_x_ori_raw;
            $titik_y_convert = array_map(fn($y) => $y / 0.7457 / $density, $titik_y_ori_raw);

            /////// convert sumbu ///////

            $ori_sumbu_x_min = $ori_sumbu_x_min_raw / 0.7457;
            $ori_sumbu_x_max = $ori_sumbu_x_max_raw / 0.7457;
            $ori_sumbu_y_min = $ori_sumbu_y_min_raw;
            $ori_sumbu_y_max = $ori_sumbu_y_max_raw;

            $convert_sumbu_x_min = $ori_sumbu_x_min_raw;
            $convert_sumbu_x_max = $ori_sumbu_x_max_raw;
            $convert_sumbu_y_min = $ori_sumbu_y_min_raw / 0.7457 / $density;
            $convert_sumbu_y_max = $ori_sumbu_y_max_raw / 0.7457 / $density;
        }
        elseif (in_array($selectedVessel, $kapal_konstan)){
            ///// KONSTAN /////

            $titik_x_ori = null;
            $titik_y_ori = null;

            $titik_x_convert = null;
            $titik_y_convert = null;

            /////// convert sumbu ///////

            $ori_sumbu_x_min = null;
            $ori_sumbu_x_max = null;
            $ori_sumbu_y_min = null;
            $ori_sumbu_y_max = null;

            $convert_sumbu_x_min = null;
            $convert_sumbu_x_max = null;
            $convert_sumbu_y_min = null;
            $convert_sumbu_y_max = null;
        }
        else {
            ////// X = BHP //////
            ////// Y = g/BHP/hr ////

            //////// convert titik ///////////

            $titik_x_ori = $titik_x_ori_raw;
            $titik_y_ori = $titik_y_ori_raw;

            $titik_x_convert = array_map(fn($x) => $x * 0.7457, $titik_x_ori_raw);
            $titik_y_convert = array_map(fn($y) => $y / 0.7457 / $density, $titik_y_ori_raw);

            /////// convert sumbu ///////

            $ori_sumbu_x_min = $ori_sumbu_x_min_raw;
            $ori_sumbu_x_max = $ori_sumbu_x_max_raw;
            $ori_sumbu_y_min = $ori_sumbu_y_min_raw;
            $ori_sumbu_y_max = $ori_sumbu_y_max_raw;

            $convert_sumbu_x_min = $ori_sumbu_x_min_raw * 0.7457;
            $convert_sumbu_x_max = $ori_sumbu_x_max_raw * 0.7457;
            $convert_sumbu_y_min = $ori_sumbu_y_min_raw / 0.7457 / $density;
            $convert_sumbu_y_max = $ori_sumbu_y_max_raw / 0.7457 / $density;
        }

        ///// cari koefisien //////

        if ($titik_x_ori !== null && $titik_y_ori !== null &&
            $titik_x_convert !== null && $titik_y_convert !== null) {

                $koefisien_ori = $this->polyfitQuadratic($titik_x_ori, $titik_y_ori);
                $koefisien_convert = $this->polyfitQuadratic($titik_x_convert, $titik_y_convert);

                list($a, $b, $c) = $koefisien_ori;
                list($d, $e, $f) = $koefisien_convert;

                ///// persamaan untuk label //////

                $persamaan_ori = "y = {$a} * x² + {$b} * x + {$c}";

                $persamaan_convert = "y = {$d} * x² + {$e} * x + {$f}";

                $grafikData_bhp = [];
                $grafikData_kw = [];

                $labelKurva_bhp = null;
                $labelKurva_kw = null;

                if (isset($persamaan_ori, $persamaan_convert)) {

                    for ($x = 10; $x <= 16000; $x += 100) {
                        //// grafik ori ////

                        $y_bhp = $a * ($x ** 2) + $b * $x + $c;
                        $grafikData_bhp[] = ['x' => $x, 'y' => round($y_bhp, 6)];

                        //// grafik convert ////

                        $y_kw = $d * ($x ** 2) + $e * $x + $f;
                        $grafikData_kw[] = ['x' => $x, 'y' => round($y_kw, 6)];
                    }

                    //// persamaan grafik ori ////

                    $labelKurva_bhp = $persamaan_ori;

                    //// persamaan grafik convert ////

                    $labelKurva_kw = $persamaan_convert;

                    //// perhitungan sfoc dan konsumsi dari grafik bhp ////

                    $sfoc_kw = $d * ($power_kw ** 2) + $e * $power_kw + $f;

                    $konsumsi = $sfoc_kw * $power_kw * $steam_time;
                    $konsumsi = ceil($konsumsi);

                    if ($power_kw == 0){
                        $sfoc_kw = 0;
                        $konsumsi = 0;
                    }
                }
        } 
        else {
            $konstan_sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/SFOC Konstan.xlsx'))->getActiveSheet();
            $konstan_data = $konstan_sheet->toArray(null, true, true, true);

            $konstan_kurva = [];
            foreach (array_slice($konstan_data, 1) as $row) {
                $vessel = strtoupper(trim($row['A']));

                if ($vessel) {
                    $konstan_kurva[$vessel] = [
                        'SFOC' => trim($row['B'] ?? ''),
                        'SATUAN' => trim($row['C'] ?? ''),
                    ];
                }
            }

            if (in_array($selectedVessel, $sfoc_konstan_bhp)){
                $sfoc_kw = $konstan_kurva[$selectedVessel]['SFOC'] / 0.7457 / $density;
            }
            else {
                $sfoc_kw = $konstan_kurva[$selectedVessel]['SFOC'] / $density ?? null;
            }

            $konsumsi = $sfoc_kw * $power_kw * $steam_time;

            $grafikData_bhp = 0; 
            $grafikData_kw = 0;
            $labelKurva_bhp = null;
            $labelKurva_kw = 'SFOC Konstan di' . ' ' . $konstan_kurva[$selectedVessel]['SFOC'] . ' ' . $konstan_kurva[$selectedVessel]['SATUAN'];
        }

        return view('po.baseline', [
            'vessels' => $vesselMap,
            'selectedVessel' => $selectedVessel,
            'grafikData_bhp' => $grafikData_bhp,
            'labelKurva_bhp' => $labelKurva_bhp,
            'grafikData_kw' => $grafikData_kw,
            'labelKurva_kw' => $labelKurva_kw,
            'density' => $density,
            'power_kw' => $power_kw,
            'steam_time' => $steam_time,
            'konsumsi' => $konsumsi ?? null,
            'sfoc_kw' => $sfoc_kw ?? null,
            'ori_x_min' => $ori_sumbu_x_min,
            'ori_x_max' => $ori_sumbu_x_max,
            'ori_y_min' => $ori_sumbu_y_min,
            'ori_y_max' => $ori_sumbu_y_max,
            'convert_x_min' => $convert_sumbu_x_min,
            'convert_x_max' => $convert_sumbu_x_max,
            'convert_y_min' => $convert_sumbu_y_min,
            'convert_y_max' => $convert_sumbu_y_max,
        ]);
    }
}