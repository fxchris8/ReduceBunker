<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use DateTime;

class UploadDinamisController extends Controller
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

    private function hitungKonsumsi(array $report, array $titik, float $density, array $konstan_kurva, array $all_vessels)
    {
        $selectedVessel = strtoupper($report['Vessel ID']);
        $power_kw = floatval($report['DAYA ME (KW)'] ?? 0);
        $steam_time = floatval($report['STEAM TIME (HOUR : MINUTE)'] ?? 0);

        // ambil titik dari Excel
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

    function reorderReport($grouped) {
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
        if (!$request->filled('report_date')) {
            return view('po.upload_dinamis', [
                'report16'    => null,
                'colored_sea' => null,
                'headers_sea' => [],
                'density'     => 950,
            ]);
        }

        $reportDate = $request->input('report_date', date('Y-m-d', strtotime('-1 day')));
        $formattedDate = \Carbon\Carbon::parse($reportDate)->format('d/m/Y');

        $density = $request->input('density') ?? 950;

        $basePayload = [
            "tanggal" => $formattedDate,
            "report_id" => "16",
        ];

        $response = Http::timeout(120)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->withBody(json_encode($basePayload), 'application/json')
            ->get('http://nanika.spil.co.id:3021/get-bunker-analysis');
        
        if (!$response->successful()) {
            return view('po.upload_dinamis', [
                'error'    => 'Gagal ambil data API (report_id: 16, status: '.$response->status().')',
                'sea_data' => [],
            ]);
        }

        $data    = $response->json();
        $reports = $data['data'] ?? [];

        $normalized = array_map([$this, 'reorderReport'], $reports);

        function deduplicateByVesselId(array $reports): array {
            $seen     = [];
            $filtered = [];

            foreach ($reports as $report) {
                $vesselid = $report['Vessel ID'] ?? null;
                if ($vesselid && !in_array($vesselid, $seen)) {
                    $seen[]     = $vesselid;
                    $filtered[] = $report;
                }
            }

            return $filtered;
        }

        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/Titik Ori Grafik.xlsx'))->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);

        $headers = $data[1];

        $titik = [];
        $vessels_titik = [];

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

                if (!in_array($vessel, $vessels_titik)) {
                    $vessels_titik[] = strtoupper($vessel);
                }

                $titik[$vessel][$axis] = $values;
            }
        }

        $konstan_sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/SFOC Konstan.xlsx'))->getActiveSheet();
        $konstan_data = $konstan_sheet->toArray(null, true, true, true);

        $konstan_kurva = [];
        $vessels_konstan = [];

        foreach (array_slice($konstan_data, 1) as $row) {
            $vessel = strtoupper(trim($row['A']));

            if ($vessel && !in_array($vessel, $vessels_konstan)) {
                $konstan_kurva[$vessel] = [
                    'SFOC' => trim($row['B'] ?? ''),
                    'SATUAN' => trim($row['C'] ?? ''),
                ];

                $vessels_konstan[] = $vessel;
            }
        }

        $power_sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/power.xlsx'))->getActiveSheet();
        $power_data = $power_sheet->toArray(null, true, true, true);

        $power = [];

        foreach (array_slice($power_data, 1) as $row) {
            $vessel = strtoupper(trim($row['A']));

            if ($vessel) {
                $power[$vessel] = [
                    'RPM' => floatval(trim($row['B'] ?? '0')),
                    'KW' => floatval(trim($row['C'] ?? '0')),
                ];
            }
        }

        $sea_data  = deduplicateByVesselId($normalized);

        $all_vessels = array_unique(array_merge($vessels_titik, $vessels_konstan));

        foreach ($sea_data as &$report) {
            $vesselId = strtoupper(trim($report['Vessel ID']));
            $dayaMe   = floatval($report['DAYA ME (KW)']);
            $powerKw  = $power[$vesselId]['KW'] ?? 0;
            $konsumsi_aktual = floatval($report['Konsumsi M/E MFO Aktual']);

            $report['Konsumsi M/E MFO Perhitungan'] = '';
            $report['Gap'] = '';
            $report['Error'] = '';

            if ($dayaMe <= $powerKw && $powerKw > 0 && $konsumsi_aktual > 0) {
                $konsumsi_perhitungan = $this->hitungKonsumsi($report, $titik, $density, $konstan_kurva, $all_vessels);
                $report['Konsumsi M/E MFO Perhitungan'] = $konsumsi_perhitungan ?? '';
                $report['Gap'] = is_numeric($report['Konsumsi M/E MFO Aktual']) && is_numeric($konsumsi_perhitungan)
                    ? $konsumsi_perhitungan - $report['Konsumsi M/E MFO Aktual']
                    : 'Tidak ada data kurva';
                $report['Error'] = is_numeric($report['Konsumsi M/E MFO Aktual']) && is_numeric($konsumsi_perhitungan) && $konsumsi_perhitungan != 0
                    ? round((($konsumsi_perhitungan - $report['Konsumsi M/E MFO Aktual']) / $konsumsi_perhitungan) * 100, 2) . ' %'
                    : '';
            }
        }

        foreach ($sea_data as $report) {
            $vesselId   = strtoupper(trim($report['Vessel ID'] ?? ''));
            $steamTime  = floatval($report['STEAM TIME (HOUR : MINUTE)'] ?? 0);
            $konsumsi   = floatval($report['Konsumsi M/E MFO Perhitungan'] ?? 0);

            if ($vesselId && $steamTime > 0 && is_numeric($report['Konsumsi M/E MFO Perhitungan']) && $konsumsi > 0) {
                $dynamicBlMe = round($konsumsi / $steamTime, 2);

                \App\Models\FuelBaseline::where('vessel_id', $vesselId)
                    ->update(['dynamic_bl_me' => $dynamicBlMe]);
            }
        }

        $colored_sea = array_map(function ($row) use ($power) {
            $vesselId = strtoupper(trim($row['Vessel ID'] ?? ''));
            $dayaMe   = floatval($row['DAYA ME (KW)'] ?? 0);
            $powerKw  = $power[$vesselId]['KW'] ?? 0;
            $konsumsi_aktual = floatval($row['Konsumsi M/E MFO Aktual'] ?? 0);

            $rawGap = $row['Gap'] ?? null;
            $selisih_aktual_baseline = ($rawGap === '' || $rawGap === null);
            $tidak_ada_kurva = ($rawGap === 'Tidak ada data kurva');

            $newRow = [];
            foreach ($row as $key => $value) {
                $class = '';
                $message = '';

                if ($key === 'DAYA ME (KW)') {
                    if ($dayaMe == 0.0) {
                        $class = ' bg-red-200 font-semibold';
                        $message = 'DAYA ME kosong (0)';
                    }
                    elseif ($dayaMe < $powerKw) {
                        $class = '';
                        $message = 'DAYA ME lebih kecil dari baseline power';
                    } elseif ($selisih_aktual_baseline && $powerKw > 0) {
                        $diffRatio = abs($dayaMe - $powerKw) / $powerKw;
                        if ($diffRatio >= 0.1) {
                            $class = ' bg-red-200 font-semibold';
                            $message = 'Perbedaan ≥ 10% dari baseline power';
                        } else {
                            $class = ' bg-yellow-200 font-semibold';
                            $message = 'Perbedaan < 10% dari baseline power';
                        }
                    }
                }

                if ($key === 'Konsumsi M/E MFO Aktual') {
                    if ($konsumsi_aktual == 0) {
                        $class = ' bg-red-200 font-semibold';
                        $message = 'Konsumsi aktual kosong (0)';
                    }
                }

                if ($key === 'Gap') {
                    if ($tidak_ada_kurva) {
                        $class = ' bg-yellow-200 font-semibold';
                        $message = 'Tidak ada data kurva';
                    }
                }

                $newRow[$key] = [
                    'value' => $value,
                    'class' => $class,
                    'message' => $message
                ];
            }

            return $newRow;
        }, $sea_data);

        $headers_sea = [
            'Vessel ID', 'Tanggal', 'DEPARTURE', 'DESTINATION', 'Steam Distance (Miles)',
            'Ship Speed (Knots)', 'Steam Time (Hour)', 'Daya ME (KW)',
            'Konsumsi M/E MFO Aktual', 'Konsumsi M/E MFO Perhitungan', 'Gap', 'Error'
        ];

        session(['headers_sea' => $headers_sea, 'sea_anomaly' => $sea_data, 'report_date' => $reportDate, 'density' => $density, 'colored_sea' => $colored_sea]);

        return view('po.upload_dinamis', [
            'headers_sea' => $headers_sea,
            'report16' => $sea_data,
            'density' => $density,
            'colored_sea' => $colored_sea,
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
                ];
            }
        }

        return $vesselEmails;
    }

    private function processEmailRow($row, $sheetName, $vesselEmails)
    {
        $primaryRole = 'Email Kapal';
        $ccRoles = ['Email SS/SI', 'Email MT', 'Email MN', 'Email DGM', 'Email GM', 'Email DPA', 'Email SSB01'];

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
                    </tr></thead><tbody>';

                    $me_hsd_val = $row['M/E HSD']['value'] ?? 0;
                    $manuvering_time_val = $row['MANEUVERING TIME (HOURS)']['value'] ?? 0;
                    $bl_me_val = $row['BL M/E']['value'] ?? 0;
                    $me_maneuv_val = $row['ME Maneuvering Cons. (L/H)']['value'] ?? 0;

                    $htmlBody .= "<tr>
                        <td>" . number_format($me_hsd_val, 2) . "</td>
                        <td>" . number_format($manuvering_time_val, 2) . "</td>
                        <td>" . number_format($bl_me_val, 2) . "</td>
                        <td>" . number_format($me_maneuv_val, 2) . "</td>
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
                    </tr></thead><tbody>';

                    $ae_mfo_val = $row['A/E MFO']['value'] ?? 0;
                    $ae_hsd_val = $row['A/E HSD']['value'] ?? 0;
                    $genset_hsd_val = $row['GENSET CONSUMPTION - HSD']['value'] ?? 0;
                    $bl_ae_val = $row['BL A/E (L/Day)']['value'] ?? 0;
                    $total_ae = $row['AE Consumption']['value'] ?? 0;

                    $htmlBody .= "<tr>
                        <td>" . number_format($ae_mfo_val, 2) . "</td>
                        <td>" . number_format($ae_hsd_val, 2) . "</td>
                        <td>" . number_format($genset_hsd_val, 2) . "</td>
                        <td>" . number_format($bl_ae_val, 2) . "</td>
                        <td>" . number_format($total_ae, 2) . "</td>
                    </tr>";

                    $htmlBody .= '</tbody></table>';
                    break;

                case 'EXCESS ME MFO L/NM (%)':
                    $htmlBody .= "<br>Terdapat <b>pemakaian ME MFO berlebih</b>.<br>";
                    $htmlBody .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; text-align: center; margin-top: 20px;">';
                    $htmlBody .= '<thead><tr>
                        <th>Steam Distance (Miles)</th>
                        <th>Steam Time (Hour)</th>
                        <th>M/E MFO</th>
                        <th>BL L/NM</th>
                        <th>L/NM</th>
                    </tr></thead><tbody>';

                    $steam_distance_val = $row['STEAM. DIST.']['value'] ?? 0;
                    $steam_time_val = $row['STEAM TIME (HOUR : MINUTE)']['value'] ?? 0;
                    $me_mfo_val = $row['M/E MFO']['value'] ?? 0;
                    $bl_ln_val = $row['BL L/NM']['value'] ?? 0;
                    $lnm_val = $row['L/NM']['value'] ?? 0;

                    $htmlBody .= "<tr>
                        <td>" . number_format($steam_distance_val, 2) . "</td>
                        <td>" . number_format($steam_time_val, 2) . "</td>
                        <td>" . number_format($me_mfo_val, 2) . "</td>
                        <td>" . number_format($bl_ln_val, 2) . "</td>
                        <td>" . number_format($lnm_val, 2) . "</td>
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

        return redirect('/consumption-analysis/dinamis')
            ->with('success_email', 'All e-mails sent successfully.');
    }
}