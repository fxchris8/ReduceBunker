<?php

namespace App\Http\Controllers;

set_time_limit(0);

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;


class PlanningController extends Controller
{
    private function loadLnmMap(): array
    {
        $filePath = storage_path('app/L_NM.xlsx');
        if (!file_exists($filePath)) {
            \Log::error("File L_NM.xlsx tidak ditemukan di $filePath");
            return [];
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $map = [];
        foreach (array_slice($rows, 1) as $row) { // skip header
            $vessel = strtoupper(trim($row['B'] ?? ''));
            $mfo    = trim($row['C'] ?? '');
            $hsd    = trim($row['D'] ?? '');
            if ($vessel && $mfo && $hsd) {
                $map[$vessel] = [
                    'mfo' => (float) $mfo,
                    'hsd' => (float) $hsd,
                ];
            }
        }
        return $map;
    }

    private function splitRouteByPosition(string $currentRouteWithNext, string $position, ?string $etbDate, string $noonReportDate): array
    {
        $routeArray = explode('.', $currentRouteWithNext);
        $positionArray = explode('.', $position);
        $positionLength = count($positionArray);

        $etb = $etbDate ? \Carbon\Carbon::createFromFormat('d/m/Y', $etbDate) : null;
        $noon = \Carbon\Carbon::createFromFormat('d/m/Y', $noonReportDate);
        $dayDiff = $etb ? $etb->diffInDays($noon, false) : null;

        $matchIndexes = [];

        // Cari index pertama dari $positionArray dalam $routeArray
        for ($i = 0; $i <= count($routeArray) - $positionLength; $i++) {
            $slice = array_slice($routeArray, $i, $positionLength);
            if ($slice === $positionArray) {
                $matchIndexes[] = $i;
            }
        }

        if (empty($matchIndexes)) {
            return [$currentRouteWithNext, ''];
        }

        $useLastMatch = $dayDiff !== null && $dayDiff <= 2;
        $matchIndex = $useLastMatch ? end($matchIndexes) : $matchIndexes[0];

        // Bagi array menjadi dua bagian
        $firstPart = array_slice($routeArray, 0, $matchIndex + 1); // +1 agar posisi awal ikut
        $secondPart = array_slice($routeArray, $matchIndex + 1);

        return [implode('.', $firstPart), implode('.', $secondPart)];
    }


    function reorderReport($grouped, $noon_report_groupedByVessel = [], $l_nm_map = [], $port_id_map = [], $jarak_map = [], string $dvs_formattedDate = '', string $noon_report_formattedDate = '') {
        $currentRoute = $grouped['from_port'] ?? '';
        $nextRoute    = $grouped['sailing_route'] ?? '';

        // Ambil part pertama dari next route
        $first_next_route = '';
        if ($nextRoute !== '') {
            $parts = preg_split('/\s*-\s*|,|_|\s+|\./', $nextRoute);
            $parts = array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));
            $first_next_route = $parts[0] ?? '';
        }

        // Tambahkan part pertama next route ke current route jika tidak kosong
        $currentRouteWithNext = $currentRoute;
        if ($currentRoute !== '' && $first_next_route !== '') {
            $currentRouteWithNext .= '.' . $first_next_route;
        }

        $calculateDistance = function (?string $route) use ($jarak_map) {
            static $cache = [];
            $route = strtoupper(trim($route ?? ''));
            if ($route === '') return null;
            if (isset($cache[$route])) return $cache[$route];

            \Log::info("\n");
            \Log::info("Route : $route");

            $parts = preg_split('/\s*-\s*|,|_|\s+|\./', $route);
            $parts = array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));

            $total = null;
            $missing = [];

            for ($i = 0; $i < count($parts) - 1; $i++) {
                $o = $parts[$i];
                $d = $parts[$i + 1];

                if ($o == $d) {
                    continue;
                }
                
                if ($o !== $d && isset($jarak_map[$o][$d])) {
                    $total += (float) $jarak_map[$o][$d];
                }
                else {
                    $missing[] = "Dari $o ke $d";
                }
            }

            if (!empty($missing)) {
                foreach ($missing as $miss) {
                    \Log::info("- $miss");
                }
                return null;
            }

            return $cache[$route] = $total;
        };

        $vesselRaw = $grouped['vesselid'] ?? '';
        $vesselKey = strtoupper(trim($vesselRaw));

        // Hitung jarak
        // $distanceCurrent = $calculateDistance($currentRouteWithNext);
        $distanceNext = $calculateDistance($nextRoute);

        //////////////////////////////////////////////////////////////////////////

        $lnm_hsd = $l_nm_map[$vesselKey]['hsd'] ?? null;
        $lnm_mfo = $l_nm_map[$vesselKey]['mfo'] ?? null;

        //////////////////////////////////////////////////////////////////////////

        $rob_hsd_sebelumnya = null;
        $rob_mfo_sebelumnya = null;
        $dtg = null;
        $departureName = '';
        $destinationName = '';
        $departurePort = '';
        $destinationPort = '';
        $position = '';
        $distanceCurrent = null;
        $part1 = '';
        $part2 = '';
        $part2_distance = '';

        if (isset($noon_report_groupedByVessel[$vesselKey])) {
            $robRow = $noon_report_groupedByVessel[$vesselKey];

            $rob_hsd_sebelumnya = $robRow['rob_hsd'] ?? null;
            $rob_mfo_sebelumnya = $robRow['rob_mfo'] ?? null;
            
            $dtg = $robRow['distance_to_go'] ?? null;

            $departureName = strtoupper(trim($robRow['departure'] ?? ''));
            $destinationName = strtoupper(trim($robRow['destination'] ?? ''));

            $departurePort = $port_id_map[$departureName] ?? null;
            $destinationPort = $port_id_map[$destinationName] ?? null;

            if ($departurePort || $destinationPort) {
                $position = $departurePort . '.' . $destinationPort;
            } 
        }

        $total_current_route = count(explode('.', $currentRouteWithNext));

        // sea
        if ($dtg !== null) {
            $etb = $dvs_formattedDate;
            $noonReport = $noon_report_formattedDate;

            list($part1, $part2) = $this->splitRouteByPosition($currentRouteWithNext, $position, $etb, $noonReport);

            $total_part2_route = count(explode('.', $part2));

            if ($part2 !== ''){
                if ($total_part2_route == 1) {
                    $distanceCurrent = $dtg;
                }
                if ($total_part2_route > 1) {
                    $part2_distance = $calculateDistance($part2);
                    $distanceCurrent = $dtg + $part2_distance;
                }
            } 
            else {
                $distanceCurrent = null;
            }
        } 
        // port
        else {
            $distanceCurrent = 0;
        }

        //////////////////////////////////////////////////////////////////////////

        if (
            $rob_hsd_sebelumnya !== null && $rob_mfo_sebelumnya !== null && 
            $distanceCurrent !== null && 
            $distanceNext >= 0 
            // && $lnm_hsd !== null && $lnm_mfo !== null
            ) {
            $rob_hsd_berthing = $rob_hsd_sebelumnya - ($distanceCurrent * $lnm_hsd) ?? null;
            $rob_mfo_berthing = $rob_mfo_sebelumnya - ($distanceCurrent * $lnm_mfo) ?? null;

            $kebutuhan_hsd_next_route = ($distanceNext * $lnm_hsd) ?? null;
            $kebutuhan_mfo_next_route = ($distanceNext * $lnm_mfo) ?? null;

            $selisih_hsd = $kebutuhan_hsd_next_route - $rob_hsd_berthing;
            $selisih_mfo = $kebutuhan_mfo_next_route - $rob_mfo_berthing;

            if ($selisih_hsd >= 0) {
                $pengisian_hsd = ceil(($selisih_hsd * 1.1) / 5000) * 5000;
            } else {
                $pengisian_hsd = 0;
            }

            if ($selisih_mfo >= 0) {
                $pengisian_mfo = ceil(($selisih_mfo * 1.1) / 5000) * 5000;
            } else {
                $pengisian_mfo = 0;
            }
        } else {
            $rob_hsd_berthing = null;
            $rob_mfo_berthing = null;
            $kebutuhan_hsd_next_route = null;
            $kebutuhan_mfo_next_route = null;
            $pengisian_hsd = null;
            $pengisian_mfo = null;
            // $distanceNext = null;
            // $rob_hsd_sebelumnya = null;
            // $rob_mfo_sebelumnya = null;
        }
                        
        $ordered = [
            'Vessel ID' => $grouped['vesselid'] ?? null,
            'Voyage' => $grouped['voyage'] ?? null,

            'New Current Route (FROM)' => $currentRouteWithNext,

            'ETA' => $grouped['eta'] ?? null,
            'ETB' => $grouped['etb'] ?? null,
            'ETD' => $grouped['etd'] ?? null,

            'Next Route (Sailing Route)' => $grouped['sailing_route'] ?? null,

            'Distance to Go' => $dtg,

            'Departure Name' => $departureName,
            'Destination Name' => $destinationName,

            'Departure Port' => $departurePort,
            'Destination Port' => $destinationPort,

            'Position' => $position,

            'Part 1' => $part1,
            'Part 2' => $part2,

            'Part 2 Distance' => $part2_distance,

            'Jarak Sisa Current Route' => $distanceCurrent,
            // 'Total jarak voyage' => $calculateDistance($currentRouteWithNext),

            'Jarak Next Route' => $distanceNext,

            'ROB HSD Sebelumnya' => $rob_hsd_sebelumnya,
            'ROB MFO Sebelumnya' => $rob_mfo_sebelumnya,

            'L/NM HSD' => $lnm_hsd,
            'L/NM MFO' => $lnm_mfo,

            'ROB HSD Arrival' => $rob_hsd_berthing,
            'ROB MFO Arrival' => $rob_mfo_berthing,

            'Kebutuhan HSD Next Route' => $kebutuhan_hsd_next_route,
            'Kebutuhan MFO Next Route' => $kebutuhan_mfo_next_route,

            'Pengisian HSD' => $pengisian_hsd,
            'Pengisian MFO' => $pengisian_mfo,
            ];

        return $ordered;
    }

    private function generatePlanningExcel(array $all_dvs_Reports, string $dvs_formattedDate, string $dvs_formattedNextWeekDate): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        // $rowIndex = 1;
        $header = [
            'Vessel ID',
            'Voyage',
            'New Current Route (FROM)',
            'ETA',
            'ETB',
            'ETD',
            'Next Route (Sailing Route)',
            'Distance to Go',
            'Departure Name',
            'Destination Name',
            'Departure Port',
            'Destination Port',
            'Position',	
            'Part 1',
            'Part 2',
            'Part 2 Distance',	
            'Jarak Sisa Current Route',
            'Jarak Next Route',
            'ROB HSD Sebelumnya',
            'ROB MFO Sebelumnya',
            'L/NM HSD',
            'L/NM MFO',
            'ROB HSD Berthing',
            'ROB MFO Berthing',
            'Kebutuhan HSD Next Route',
            'Kebutuhan MFO Next Route',
            'Pengisian HSD',
            'Pengisian MFO',
            'Koreksi',
        ];

        $sheet->fromArray($header, null, 'A1');
        $lastCol = Coordinate::stringFromColumnIndex(count($header));

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'd0d0d0'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $rowIndex = 2;
        foreach ($all_dvs_Reports as $reportRow) {
            $reportRow['Koreksi'] = ''; // tambahkan kolom koreksi kosong
            $sheet->fromArray(array_values($reportRow), null, "A{$rowIndex}");
            $rowIndex++;
        }

        $lastRow = $rowIndex - 1;

        $bodyRange = "A2:{$lastCol}{$lastRow}";
        $sheet->getStyle($bodyRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Zebra striping dengan conditional formatting
        $conditionalStyles = $sheet->getStyle($bodyRange)->getConditionalStyles();

        $evenRowCondition = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
        $evenRowCondition->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_EXPRESSION);
        $evenRowCondition->addCondition('MOD(ROW(),2)=0');
        $evenRowCondition->getStyle()->getFill()->setFillType(
            \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID
        )->getStartColor()->setRGB('FFFFFF');

        $oddRowCondition = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
        $oddRowCondition->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_EXPRESSION);
        $oddRowCondition->addCondition('MOD(ROW(),2)=1');
        $oddRowCondition->getStyle()->getFill()->setFillType(
            \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID
        )->getStartColor()->setRGB('E0E0E0');

        $conditionalStyles[] = $evenRowCondition;
        $conditionalStyles[] = $oddRowCondition;

        $sheet->getStyle($bodyRange)->setConditionalStyles($conditionalStyles);

        foreach (range(1, count($header)) as $colIndex) {
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        // Save to temp file
        $cleanDate = str_replace(['/', ':', ' '], '_', $dvs_formattedDate);
        $cleannextWeekDate = str_replace(['/', ':', ' '], '_', $dvs_formattedNextWeekDate);
        $tempFile = tempnam(sys_get_temp_dir(), 'planning_from_' . $cleanDate . '_until_' . $cleannextWeekDate) . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }

    public function show(Request $request)
    {   
        $reportDate = $request->input('report_date', date('Y-m-d'));
        $next_week_date = $request->input('next_week_date', date('Y-m-d', strtotime('+7 days')));
        
        $dvs_formattedDate = \Carbon\Carbon::parse($reportDate)->format('d/m/Y');
        $dvs_formattedNextWeekDate = \Carbon\Carbon::parse($next_week_date)->format('d/m/Y');

        $dvs_basePayload = [
            "tanggal_awal" => $dvs_formattedDate,
            "tanggal_akhir" => $dvs_formattedNextWeekDate,
        ];

        $all_dvs_Reports = [];

        $dvs_payload = $dvs_basePayload;
        
        $response = Http::timeout(120)
        ->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->withBody(json_encode($dvs_payload), 'application/json')
        ->get('http://nanika.spil.co.id:3021/get-data-dvs');

        if (!$response->successful()) {
            return view('po.planning', [
                'error' => 'Gagal ambil data API (report_id: '.$reportId.', status: '.$response->status().')',
            ]);
        }

        $dvs_data = $response->json();
        $dvs_reports = $dvs_data['data'] ?? [];

        $dvs_reports = array_filter($dvs_reports, function ($row) {
            return isset($row['vesselid']) && trim($row['vesselid']) !== '';
        });

        \Log::info("\n");
        \Log::info(str_repeat('-', 50) . PHP_EOL);
        \Log::info("\n");

        ////////////////////////////////////////////////////////////////////////

        $noon_report_formattedDate = \Carbon\Carbon::now()->subDay()->format('d/m/Y');

        $noon_report_basePayload = [
            "tanggal" => $noon_report_formattedDate,
        ];

        $reportIds = [14, 16];
        $noon_report_allReports = [];

        foreach ($reportIds as $reportId) {
            $rob_payload = $noon_report_basePayload;
            $rob_payload['report_id'] = (string)$reportId;

            $response = Http::timeout(120)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->withBody(json_encode($rob_payload), 'application/json')
            ->get('http://nanika.spil.co.id:3021/get-bunker-analysis');

            if (!$response->successful()) {
                return view('po.planning', [
                    'error' => 'Gagal ambil data API (report_id: '.$reportId.', status: '.$response->status().')',
                    'report14' => [],
                    'report16' => [],
                ]);
            }

            $rob_data = $response->json();
            $rob_reports = $rob_data['data'] ?? [];
            $noon_report_allReports = array_merge($noon_report_allReports, $rob_reports);
        }

        // Kelompokkan dan ambil data terakhir per vesselid berdasarkan tanggal
        $noon_report_groupedByVessel = [];

        foreach ($noon_report_allReports as $row) {
            $vesselid = $row['vesselid'] ?? null;
            $tanggal = $row['tanggal'] ?? null;

            if ($vesselid && $tanggal) {
                $row['tanggal_obj'] = \Carbon\Carbon::parse($tanggal); // simpan objek Carbon untuk sorting

                if (!isset($noon_report_groupedByVessel[$vesselid])) {
                    $noon_report_groupedByVessel[$vesselid] = [];
                }

                $noon_report_groupedByVessel[$vesselid][] = $row;
            }
        }

        // Ambil hanya 1 data terakhir per vessel
        $noon_report_groupedByVessel = collect($noon_report_groupedByVessel)->map(function ($reports) {
            return collect($reports)
                ->sortByDesc(fn($r) => $r['tanggal_obj'])
                ->map(fn($r) => [
                    'rob_hsd' => $r['rob_hsd'] ?? null,
                    'rob_mfo' => $r['rob_mfo'] ?? null,
                    'distance_to_go' => $r['distance_to_go'] ?? null,
                    'departure' => isset($r['departure']) ? strtoupper($r['departure']) : null,
                    'destination' => isset($r['destination']) ? strtoupper($r['destination']) : null,
                ])
                ->first();
        })->toArray();

        ///////////////////////////////////////////////////////////////////////////

        $l_nm_map = $this->loadLnmMap();

        ///////////////////////////////////////////////////////////////////////////

        $port_id_basePayload = [
            "load_port" => "-",
            "disc_port" => "-",
        ];

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->withBody(json_encode($port_id_basePayload), 'application/json')
        ->get('http://nanika.spil.co.id:3021/get-master-route');

        if (!$response->successful()) {
            return view('po.planning', [
                'error' => 'Gagal ambil data API (status: '.$response->status().')',
            ]);
        }

        $port_id_data = $response->json();
        $port_id_map = [];

        foreach ($port_id_data['data'] as $port) {
            $discportname = trim($port['discportname'] ?? '');
            $discport_unportid = trim($port['discport_unportid'] ?? '');

            $loadportname = trim($port['loadportname'] ?? '');
            $loadport_unportid = trim($port['loadport_unportid'] ?? '');

            if ($discportname !== '') {
                $port_id_map[$discportname] = $discport_unportid;
            }

            if ($loadportname !== '') {
                $port_id_map[$loadportname] = $loadport_unportid;
            }

        }

        $port_id_map['BAUBAU'] = 'IDBUW';
        $port_id_map['SAMARINDA'] = 'IDSRI';
        $port_id_map['BALIKPAPAN'] = 'IDBPN';
        $port_id_map['CILEGON'] = 'IDCGN';
        $port_id_map['LAMPUNG'] = 'IDTKG';
        $port_id_map['PALEMBANG'] = 'IDPLM';
        $port_id_map['BOMBANA'] = 'IDBOE';
        $port_id_map['MAKASAR'] = 'IDMAK';

        //////////////////////////////////////////////////////////////////////

        $jarak_basePayload = [
            "load_port" => "-",
            "disc_port" => "-",
        ];

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->withBody(json_encode($jarak_basePayload), 'application/json')
        ->get('http://nanika.spil.co.id:3021/get-master-route');

        if (!$response->successful()) {
            return view('po.planning', [
                'error' => 'Gagal ambil data API (status: '.$response->status().')',
            ]);
        }

        $jarak_data = $response->json();
        $jarak_map = [];

        foreach ($jarak_data['data'] as $route) {
            $from = trim($route['loadport_unportid'] ?? '');
            $to   = trim($route['discport_unportid'] ?? '');
            $dist = floatval($route['nmile'] ?? 0);

            $from_name = trim($route['loadportname'] ?? '');
            $to_name   = trim($route['discportname'] ?? '');

            if ($from !== '' && $to !== '') {
                if (!isset($jarak_map[$from][$to]) || $jarak_map[$from][$to] < $dist) {
                    $jarak_map[$from][$to] = $dist;
                }
            }
        }

        /////////////////////////////////////////////////////////////////////

        // Pass ke reorderReport
        $all_dvs_Reports = array_map(
            fn($grouped) => $this->reorderReport($grouped, $noon_report_groupedByVessel, $l_nm_map, $port_id_map, $jarak_map, $dvs_formattedDate, $noon_report_formattedDate),
            $dvs_reports
        );

        $all_dvs_Reports = array_filter($all_dvs_Reports, fn($r) => !empty($r['ETB']));

        usort($all_dvs_Reports, function ($a, $b) {
            $etbA = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $a['ETB'] ?? '01/01/1900 00:00');
            $etbB = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $b['ETB'] ?? '01/01/1900 00:00');
            return $etbA->lt($etbB) ? -1 : ($etbA->gt($etbB) ? 1 : 0);
        });


        //////////////////////////////////////////////////////////////////////////

        $filePath = $this->generatePlanningExcel($all_dvs_Reports, $dvs_formattedDate, $dvs_formattedNextWeekDate);

        Mail::raw('Berikut terlampir hasil Refueling Planning untuk tanggal ' . $dvs_formattedDate . ' hingga tanggal ' . $dvs_formattedNextWeekDate . '.', function ($message) use ($filePath, $dvs_formattedDate, $dvs_formattedNextWeekDate) {
            $cleanDate = str_replace(['/', ':', ' '], '_', $dvs_formattedDate);
            $cleannextWeekDate = str_replace(['/', ':', ' '], '_', $dvs_formattedNextWeekDate);
            $message->to('marulihtgl12@gmail.com')
                    ->subject('Refueling Planning Excel')
                    ->attach($filePath, [
                        'as' => 'refueling_planning_from_' . $cleanDate . '_until_' . $cleannextWeekDate . '.xlsx',
                        'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]);
        });

        session([
            'planning_reports' => $all_dvs_Reports,
            'planning_date' => $dvs_formattedDate,
            'planning_next_week_date' => $dvs_formattedNextWeekDate,
        ]);

        return view('po.planning', [
            'reportDate' => $dvs_formattedDate,
            'nextWeekDate' => $dvs_formattedNextWeekDate,
            'headerRows' => $all_dvs_Reports ? array_keys($all_dvs_Reports[0]) : [],
            'report' => $all_dvs_Reports,
        ]);
    }

    public function download()
    {
        $reports = session('planning_reports');
        $date = session('planning_date');
        $next_week_date = session('planning_next_week_date');

        $filePath = $this->generatePlanningExcel($reports, $date, $next_week_date);
        $filename = 'refueling_planning_from_' . str_replace(['/', ':', ' '], '_', $date) . '_until_' . str_replace(['/', ':', ' '], '_', $next_week_date) . '.xlsx';

        return response()->download($filePath, $filename)->deleteFileAfterSend(true);
    }

}