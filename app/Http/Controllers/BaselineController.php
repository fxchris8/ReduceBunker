<?php

namespace App\Http\Controllers;

set_time_limit(0);

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class BaselineController extends Controller
{
    public function show(Request $request)
    { 
        $selectedVessel = $request->input('vessel') ?? 'ABBRW';
        $density = $request->input('density') ?? 950;

        $power_kw = $request->input('power_kw');
        $steam_time = $request->input('steam_time');

        $vesselMap = ['HSA', 'OGO'];
        // $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/fleet.xlsx'))->getActiveSheet();
        
        // foreach (array_slice($sheet->toArray(null, true, true, true), 1) as $row) {
        //     $vessel = strtoupper(trim($row['B']));
        //     if ($vessel !== '') {
        //         $vesselMap[] = $vessel;
        //     }
        // }

        sort($vesselMap);

        $koefisienMap = [];
        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/Koefisien Grafik BHP (Original).xlsx'))->getActiveSheet();
        
        foreach (array_slice($sheet->toArray(null, true, true, true), 1) as $row) {
            $vessel = strtoupper(trim($row['A']));
            $koefisien1 = floatval(trim($row['B']));
            $koefisien2 = floatval(trim($row['C']));
            $koefisien3 = floatval(trim($row['D']));

            if ($vessel !== '') {
                $koefisienMap[$vessel] = [
                    'koefisien1' => $koefisien1,
                    'koefisien2' => $koefisien2,
                    'koefisien3' => $koefisien3,
                ];
            }
        }

        $selectedKoefisien = $koefisienMap[$selectedVessel] ?? null;

        $grafikData_bhp = [];
        $grafikData_kw = [];

        if ($selectedKoefisien) {
            $a = $selectedKoefisien['koefisien1'];
            $b = $selectedKoefisien['koefisien2'];
            $c = $selectedKoefisien['koefisien3'];

            $aFormatted = number_format($a, 15, '.', ''); // 12 digit desimal
            $bFormatted = number_format($b, 11, '.', '');
            $cFormatted = number_format($c, 6, '.', '');

            for ($x = 4000; $x <= 16000; $x += 100) {
                $y_bhp = $aFormatted * $x * $x + $bFormatted * $x + $cFormatted;
                $grafikData_bhp[] = ['x' => $x, 'y' => round($y_bhp, 6)];

                $y_kw = $y_bhp / 0.7457 / $density;
                $grafikData_kw[] = ['x' => $x, 'y' => round($y_kw, 6)];
            }
        }

        $labelKurva_bhp = null;
        $labelKurva_kw = null;

        if (isset($aFormatted, $bFormatted, $cFormatted)) {
            $labelKurva_bhp = "y = {$aFormatted}x² + ({$bFormatted})x + {$cFormatted}";

            $a_kw = $aFormatted / (0.7457 / $density);
            $b_kw = $bFormatted / (0.7457 / $density);
            $c_kw = $cFormatted / (0.7457 / $density);

            $a_kw_formatted = number_format($a_kw, 15, '.', '');
            $b_kw_formatted = number_format($b_kw, 11, '.', '');
            $c_kw_formatted = number_format($c_kw, 6, '.', '');

            $labelKurva_kw = "y = {$a_kw_formatted}x² + ({$b_kw_formatted})x + {$c_kw_formatted}";

            $sfoc_kw_raw = $aFormatted * ($power_kw ** 2) + $bFormatted * $power_kw + $cFormatted;
            $sfoc_kw = $sfoc_kw_raw / 0.7457 / $density;

            $konsumsi = $sfoc_kw * $power_kw * $steam_time;
            $konsumsi = round($konsumsi, 0); // in kg
        }

        return view('po.baseline', [
            'vessels' => $vesselMap,
            'selectedVessel' => $selectedVessel,
            'selectedKoefisien' => $selectedKoefisien,
            'grafikData_bhp' => $grafikData_bhp,
            'labelKurva_bhp' => $labelKurva_bhp,
            'grafikData_kw' => $grafikData_kw,
            'labelKurva_kw' => $labelKurva_kw,
            'density' => $density,
            'power_kw' => $power_kw,
            'steam_time' => $steam_time,
            'konsumsi' => $konsumsi ?? null,
            'sfoc_kw' => $sfoc_kw ?? null,
        ]);
    }
}