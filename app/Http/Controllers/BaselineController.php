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
        $selectedVessel = $request->input('vessel') ?? 'HSA';
        $density = $request->input('density') ?? 950;

        $power_kw = $request->input('power_kw');
        $power_bhp = $power_kw / 0.7457;

        $steam_time = $request->input('steam_time');

        ///// markdown //////

        $vesselMap = [];
        // $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/fleet.xlsx'))->getActiveSheet();
        
        // foreach (array_slice($sheet->toArray(null, true, true, true), 1) as $row) {
        //     $vessel = strtoupper(trim($row['B']));
        //     if ($vessel !== '') {
        //         $vesselMap[] = $vessel;
        //     }
        // }

        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/Koefisien Grafik BHP (Original).xlsx'))->getActiveSheet();
        
        foreach (array_slice($sheet->toArray(null, true, true, true), 1) as $row) {
            $vessel = strtoupper(trim($row['A']));
            if ($vessel !== '') {
                $vesselMap[] = $vessel;
            }
        }

        sort($vesselMap);

        ///// koefisien /////

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

            ////// koefisien bhp //////

            $a = $selectedKoefisien['koefisien1'];
            $b = $selectedKoefisien['koefisien2'];
            $c = $selectedKoefisien['koefisien3'];

            $aFormatted = number_format($a, 15, '.', ''); // 12 digit desimal
            $bFormatted = number_format($b, 11, '.', '');
            $cFormatted = number_format($c, 6, '.', '');

            //// grafik /////

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

            //// persamaan grafik bhp ////

            $labelKurva_bhp = "y = {$aFormatted}x² + ({$bFormatted})x + {$cFormatted}";

            //// persamaan grafik kw ////

            $a_kw = $aFormatted / (0.7457 / $density);
            $b_kw = $bFormatted / (0.7457 / $density);
            $c_kw = $cFormatted / (0.7457 / $density);

            $a_kw_formatted = number_format($a_kw, 15, '.', '');
            $b_kw_formatted = number_format($b_kw, 11, '.', '');
            $c_kw_formatted = number_format($c_kw, 6, '.', '');

            $labelKurva_kw = "y = {$a_kw_formatted}x² + ({$b_kw_formatted})x + {$c_kw_formatted}";

            //// perhitungan sfoc dan konsumsi dari grafik bhp ////

            $sfoc_bhp = $aFormatted * ($power_bhp ** 2) + $bFormatted * $power_bhp + $cFormatted;
            $sfoc_kw = $sfoc_bhp / 0.7457 / $density;

            $konsumsi = $sfoc_kw * $power_kw * $steam_time;
            $konsumsi = round($konsumsi, 0); 
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