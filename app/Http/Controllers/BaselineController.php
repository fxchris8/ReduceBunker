<?php

namespace App\Http\Controllers;

set_time_limit(0);

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BaselineController extends Controller
{
    private function polyfitQuadratic(array $x, array $y) {
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

    // Eliminasi Gauss Sederhana
    private function solveLinearSystem(array $A, array $B) {
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

    private function getExcelData()
    {
        return Cache::rememberForever('baseline_excel_data', function () {
            // Load Titik Ori
            $sheet = IOFactory::load(storage_path('app/Titik Ori Grafik.xlsx'))->getActiveSheet();
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

            // Load Sumbu
            $sumbu_sheet = IOFactory::load(storage_path('app/Sumbu Grafik.xlsx'))->getActiveSheet();
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

            // Load Konstan
            $konstan_sheet = IOFactory::load(storage_path('app/SFOC Konstan.xlsx'))->getActiveSheet();
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

            return [
                'titik' => $titik,
                'sumbu_kurva' => $sumbu_kurva,
                'konstan_kurva' => $konstan_kurva
            ];
        });
    }

    private function calculateVesselMetrics($selectedVessel, $density, $power_kw, $steam_time)
    {
        $excelData = $this->getExcelData();
        $titik = $excelData['titik'];
        $sumbu_kurva = $excelData['sumbu_kurva'];
        $konstan_kurva = $excelData['konstan_kurva'];

        $ori_sumbu_x_min_raw = $sumbu_kurva[$selectedVessel]['Ori X Min'] ?? null;
        $ori_sumbu_x_max_raw = $sumbu_kurva[$selectedVessel]['Ori X Max'] ?? null;
        $ori_sumbu_y_min_raw = $sumbu_kurva[$selectedVessel]['Ori Y Min'] ?? null;
        $ori_sumbu_y_max_raw = $sumbu_kurva[$selectedVessel]['Ori Y Max'] ?? null;

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
            $titik_x_ori = null; $titik_y_ori = null;
            $titik_x_convert = null; $titik_y_convert = null;
            $ori_sumbu_x_min = null; $ori_sumbu_x_max = null;
            $ori_sumbu_y_min = null; $ori_sumbu_y_max = null;
            $convert_sumbu_x_min = null; $convert_sumbu_x_max = null;
            $convert_sumbu_y_min = null; $convert_sumbu_y_max = null;
        }
        else {
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

        $grafikData_bhp = null;
        $grafikData_kw = null;
        $labelKurva_bhp = null;
        $labelKurva_kw = null;
        $sfoc_kw = 0;
        $konsumsi = 0;

        if ($titik_x_ori !== null && $titik_y_ori !== null && $titik_x_convert !== null && $titik_y_convert !== null) {
            $koefisien_ori = $this->polyfitQuadratic($titik_x_ori, $titik_y_ori);
            $koefisien_convert = $this->polyfitQuadratic($titik_x_convert, $titik_y_convert);

            list($a, $b, $c) = $koefisien_ori;
            list($d, $e, $f) = $koefisien_convert;

            $persamaan_ori = "y = {$a} * x² + {$b} * x + {$c}";
            $persamaan_convert = "y = {$d} * x² + {$e} * x + {$f}";

            $grafikData_bhp = [];
            $grafikData_kw = [];

            if (isset($persamaan_ori, $persamaan_convert)) {
                for ($x = 10; $x <= 16000; $x += 100) {
                    $y_bhp = $a * ($x ** 2) + $b * $x + $c;
                    $grafikData_bhp[] = ['x' => $x, 'y' => round($y_bhp, 6)];
                    
                    $y_kw = $d * ($x ** 2) + $e * $x + $f;
                    $grafikData_kw[] = ['x' => $x, 'y' => round($y_kw, 6)];
                }
                $labelKurva_bhp = $persamaan_ori;
                $labelKurva_kw = $persamaan_convert;

                $sfoc_kw = $d * ($power_kw ** 2) + $e * $power_kw + $f;
                $konsumsi = $sfoc_kw * $power_kw * $steam_time;
                $konsumsi = ceil($konsumsi);

                if ($power_kw == 0){
                    $sfoc_kw = 0;
                    $konsumsi = 0;
                }
            }
        } else {
            if (in_array($selectedVessel, $sfoc_konstan_bhp)){
                $sfoc_kw = ($konstan_kurva[$selectedVessel]['SFOC'] ?? 0) / 0.7457 / $density;
            } else {
                $sfoc_kw = ($konstan_kurva[$selectedVessel]['SFOC'] ?? 0) / $density;
            }

            $konsumsi = $sfoc_kw * $power_kw * $steam_time;
            $grafikData_bhp = 0; 
            $grafikData_kw = 0;
            $labelKurva_bhp = null;
            $labelKurva_kw = 'SFOC Konstan di ' . ($konstan_kurva[$selectedVessel]['SFOC'] ?? '') . ' ' . ($konstan_kurva[$selectedVessel]['SATUAN'] ?? '');
        }

        return [
            'grafikData_bhp' => $grafikData_bhp,
            'labelKurva_bhp' => $labelKurva_bhp,
            'grafikData_kw' => $grafikData_kw,
            'labelKurva_kw' => $labelKurva_kw,
            'sfoc_kw' => $sfoc_kw,
            'konsumsi' => $konsumsi,
            'ori_x_min' => $ori_sumbu_x_min,
            'ori_x_max' => $ori_sumbu_x_max,
            'ori_y_min' => $ori_sumbu_y_min,
            'ori_y_max' => $ori_sumbu_y_max,
            'convert_x_min' => $convert_sumbu_x_min,
            'convert_x_max' => $convert_sumbu_x_max,
            'convert_y_min' => $convert_sumbu_y_min,
            'convert_y_max' => $convert_sumbu_y_max,
        ];
    }

    public function index(Request $request)
    {
        $density = $request->input('density', 950);
        $search = trim((string) $request->input('search', ''));
        $perPage = $request->input('per_page', 10);

        $allowedPerPage = ['10', '25', '50', 'all'];
        $perPage = (string) $perPage;
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = '10';
        }

        session(['baseline_density' => $density]);

        $excelData = $this->getExcelData();
        $sumbu_kurva = $excelData['sumbu_kurva'];
        $konstan_kurva = $excelData['konstan_kurva'];

        // Get list of vessels that actually have data in Excel so we don't error out
        $validVessels = array_unique(array_merge(array_keys($sumbu_kurva), array_keys($konstan_kurva)));

        $query = DB::table('vessels')
            ->whereIn('vessel_id', $validVessels)
            ->orderBy('vessel_id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('vessel_id', 'like', "%{$search}%")
                    ->orWhere('vessel_name', 'like', "%{$search}%");
            });
        }

        if ($perPage === 'all') {
            $vessels = $query->get();
            $isPaginated = false;
        } else {
            $vessels = $query->paginate((int) $perPage)->withQueryString();
            $isPaginated = true;
        }

        $tableData = [];
        foreach ($vessels as $v) {
            $power_kw = (int) ($v->vc_main_power ?? 0);

            $metrics = $this->calculateVesselMetrics($v->vessel_id, $density, $power_kw, 1);
            $sfoc = $metrics['sfoc_kw'];
            $l_hr = $sfoc * $power_kw;

            $tableData[] = [
                'vessel_id' => $v->vessel_id,
                'vessel_name' => $v->vessel_name,
                'power_me' => $v->vc_main_power,
                'power_kw_num' => $power_kw,
                'sfoc' => round($sfoc, 6),
                'l_hr' => round($l_hr, 2),
            ];
        }

        return view('po.baseline', [
            'density' => $density,
            'tableData' => $tableData,
            'search' => $search,
            'perPage' => $perPage,
            'isPaginated' => $isPaginated,
            'vessels' => $vessels,
        ]);
    }

    public function detail(Request $request, $vessel_id)
    {
        $density = $request->input('density') ?? session('baseline_density', 950);
        $power_kw = $request->input('power_kw');
        $steam_time = $request->input('steam_time');

        $defaultPowerKw = (float) (DB::table('vessels')
            ->where('vessel_id', $vessel_id)
            ->value('vc_main_power') ?? 0);

        if ($power_kw === null || $power_kw === '') {
            $power_kw = $defaultPowerKw;
        }

        if ($steam_time === null || $steam_time === '') {
            $steam_time = 1;
        }
        
        session([
            'baseline_vessel' => $vessel_id,
            'baseline_density' => $density,
            'baseline_power_kw' => $power_kw,
            'baseline_steam_time' => $steam_time,
        ]);

        $excelData = $this->getExcelData();
        $sumbu_kurva = $excelData['sumbu_kurva'];
        $konstan_kurva = $excelData['konstan_kurva'];
        $vesselMap = array_unique(array_merge(array_keys($sumbu_kurva), array_keys($konstan_kurva)));
        sort($vesselMap);

        $metrics = $this->calculateVesselMetrics($vessel_id, $density, $power_kw, $steam_time);

        return view('po.baseline_detail', array_merge([
            'vessels' => $vesselMap,
            'selectedVessel' => $vessel_id,
            'density' => $density,
            'power_kw' => $power_kw,
            'steam_time' => $steam_time,
        ], $metrics));
    }
}