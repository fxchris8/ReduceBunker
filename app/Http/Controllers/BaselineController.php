<?php

namespace App\Http\Controllers;

set_time_limit(0);

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class BaselineController extends Controller
{
    function polyfitQuadratic(array $x, array $y) {
        $n = count($x);

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
        $selectedVessel = $request->input('vessel') ?? 'HSA';
        $density = $request->input('density') ?? 950;

        $power_kw = $request->input('power_kw');
        $power_bhp = $power_kw / 0.7457;

        $steam_time = $request->input('steam_time');

        ///// markdown dan titik //////

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

        $vesselMap = array_keys($titik);

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

        $ori_x_min = $sumbu_kurva[$selectedVessel]['Ori X Min'] ?? [];
        $ori_x_max = $sumbu_kurva[$selectedVessel]['Ori X Max'] ?? [];

        $ori_y_min = $sumbu_kurva[$selectedVessel]['Ori Y Min'] ?? [];
        $ori_y_max = $sumbu_kurva[$selectedVessel]['Ori Y Max'] ?? [];

        ///// convert titik //////

        $titik_x_ori = array_map('floatval', $titik[$selectedVessel]['X'] ?? []);
        $titik_y_ori = array_map('floatval', $titik[$selectedVessel]['Y'] ?? []);

        $kapal_kecil = ['PHK', 'PLA', 'PWE', 'TBE', 'TBI', 'TFL'];

        if (in_array($selectedVessel, $kapal_kecil)){
            $titik_x_convert = array_map(fn($x) => $x, $titik_x_ori);
            $titik_y_convert = array_map(fn($y) => $y / $density, $titik_y_ori);

            $titik_x_ori = array_map(fn($x) => $x / 0.7457, $titik_x_ori);
            $titik_y_ori = array_map(fn($y) => $y * 0.7457, $titik_y_ori);

            $convert_x_min = $ori_x_min;
            $convert_x_max = $ori_x_max;
            $convert_y_min = $ori_y_min / $density;
            $convert_y_max = $ori_y_max / $density;

            $ori_x_min = $ori_x_min / 0.7457;
            $ori_x_max = $ori_x_max / 0.7457;
            $ori_y_min = $ori_y_min * 0.7457;
            $ori_y_max = $ori_y_max * 0.7457;
        } 
        else {
            $titik_x_convert = array_map(fn($x) => $x * 0.7457, $titik_x_ori);
            $titik_y_convert = array_map(fn($y) => $y / 0.7457 / $density, $titik_y_ori);

            $ori_x_min = $ori_x_min;
            $ori_x_max = $ori_x_max;
            $ori_y_min = $ori_y_min;
            $ori_y_max = $ori_y_max;

            $convert_x_min = $ori_x_min * 0.7457;
            $convert_x_max = $ori_x_max * 0.7457;
            $convert_y_min = $ori_y_min / 0.7457 / $density;
            $convert_y_max = $ori_y_max / 0.7457 / $density;
        }

        ///// cari koefisien //////

        $koefisien_ori = $this->polyfitQuadratic($titik_x_ori, $titik_y_ori);
        $koefisien_convert = $this->polyfitQuadratic($titik_x_convert, $titik_y_convert);

        list($a, $b, $c) = $koefisien_ori;
        list($d, $e, $f) = $koefisien_convert;

        ///// persamaan untuk label //////

        $persamaan_ori = "y = {$a} * x^2 + {$b} * x + {$c}";

        $persamaan_convert = "y = {$d} * x^2 + {$e} * x + {$f}";

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
            $konsumsi = round($konsumsi, 0);
        }

        print_r($labelKurva_kw);

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
            'ori_x_min' => $ori_x_min,
            'ori_x_max' => $ori_x_max,
            'ori_y_min' => $ori_y_min,
            'ori_y_max' => $ori_y_max,
            'convert_x_min' => $convert_x_min,
            'convert_x_max' => $convert_x_max,
            'convert_y_min' => $convert_y_min,
            'convert_y_max' => $convert_y_max,
        ]);
    }
}