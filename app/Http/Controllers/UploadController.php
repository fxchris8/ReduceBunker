<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use DateTime;

class UploadController extends Controller
{   
    function reorderReport($grouped, $baselines) {
        $bl_l_nm_FilePath = storage_path('app/BL for Analysis.xlsx');
        $spreadsheet_bl_l_nm = IOFactory::load($bl_l_nm_FilePath);
        $sheet_bl_l_nm = $spreadsheet_bl_l_nm->getActiveSheet();
        $bl_l_nm_Data = $sheet_bl_l_nm->toArray(null, true, true, true);

        $bl_l_nm_map = [];

        foreach (array_slice($bl_l_nm_Data, 1) as $row) {
            $vessel = trim($row['A']); 
            $bl_l_nm   = trim($row['F']);
            if ($vessel && $bl_l_nm) {
                $bl_l_nm_map[$vessel] = ['bl_l_nm' => $bl_l_nm];
            }
        }

        $vesselRaw = $grouped['vesselid'] ?? '';
        $vesselKey = strtoupper(trim($vesselRaw));
        $vesselBaseline = $baselines->get($vesselKey);

        $bl_l_nm = $bl_l_nm_map[$vesselKey]['bl_l_nm'] ?? 10;

        $bl_mfo_db      = $vesselBaseline?->bl_mfo ?? 0;
        $bl_hsd_db      = $vesselBaseline?->bl_hsd ?? 0;
        $bl_ae_1_reffer = $vesselBaseline?->bl_ae_1_reffer ?? 0;

        \Log::info(print_r($bl_l_nm_map, true) . PHP_EOL);

        $ordered = [
            'Vessel ID' => $grouped['vesselid'] ?? null,
            'tanggal' => isset($grouped['tanggal']) 
                ? (new DateTime($grouped['tanggal']))->format('d F Y H:i') 
                : null,
            
            'POSITION' => $grouped['pos'] ?? null,
            
            'DEPARTURE PORT' => $grouped['departure'] ?? null,
            'DESTINATION' => $grouped['destination'] ?? null,

            'STEAM. DIST.' => $grouped['steam_dist'] ?? null,
            'STEAM TIME (HOUR : MINUTE)' => $grouped['steam_time'] ?? null,
            'SHIP SPEED' => $grouped['ship_speed'] ?? null,
            'PROPELLER SLIP' => $grouped['prop_slip'] ?? null,
            'ME RPM' => $grouped['me_rpm'] ?? null,

            'M/E MFO' => $grouped['me_mfo'] ?? null,
            'M/E HSD' => $grouped['me_hsd'] ?? null,

            'A/E MFO' => $grouped['ae_mfo'] ?? null,
            'A/E HSD' => $grouped['ae_hsd'] ?? null,

            'MANEUVERING TIME (HOURS)' => $grouped['duration_manuev'] ?? null,

            'BOILER HSD' => $grouped['boiler_hsd'] ?? null,
            'BOILER MFO' => $grouped['boiler_mfo'] ?? null,

            'GENSET CONSUMPTION - HSD' => $grouped['genset_consum_hsd'] ?? null,
            'EMERGENCY GENERATOR CONSUMPTION' => $grouped['emg'] ?? null,

            'TOTAL CRANE' => $grouped['total_crane'] ?? null, 
            'CRANE DURATION' => $grouped['crane_duration'] ?? null,

            'LOAD A/E 1 (KW)' => $grouped['load_ae_1'] ?? null,
            'LOAD A/E 2 (KW)' => $grouped['load_ae_2'] ?? null,
            'LOAD A/E 3 (KW)' => $grouped['load_ae_3'] ?? null,
            'LOAD A/E 4 (KW)' => $grouped['load_ae_4'] ?? null,

            'AE PARAREL DURATION' => $grouped['ae_pararel_duration'] ?? null,

            'REEFER 20"' => $grouped['reefer20'] ?? null,
            'REEFER 40"' => $grouped['reefer40'] ?? null,
            
            'BL M/E' => $grouped['bl_me_hsd'] ?? null,
            'ME Maneuvering Cons. (L/H)' => $grouped['me_manuev_consum'] ?? null,
            'SELISIH ME Maneuvering' => $grouped['selisih'] ?? null,

            'BL L/NM' => $bl_l_nm,

            'BL MFO'    => $bl_mfo_db,
            'BL HSD'    => $bl_hsd_db,
            'BL REFFER' => $bl_ae_1_reffer,

            'L/NM' => (
                isset($grouped['steam_time']) && $grouped['steam_time'] >= 24 && !empty($grouped['steam_dist'])
            ) ? (
                ($grouped['me_mfo'] ?? 0) / $grouped['steam_dist']
            ) : 0,
            
            'EXCESS ME MFO L/NM (%)' => (
                isset(
                $bl_l_nm, 
                $grouped['steam_time'], $grouped['steam_dist']) && $grouped['steam_time'] >= 24 && !empty($grouped['steam_dist'])
            ) ? (
                ((($grouped['me_mfo'] ?? 0) / $grouped['steam_dist']) > 0)
                    ? (($bl_l_nm - (($grouped['me_mfo'] ?? 0) / $grouped['steam_dist'])) / $bl_l_nm) * 100
                    : 0
            ) : 0,

            'BL A/E (L/Day)' => $grouped['bl_ae'] ?? null,
            'AE Consumption' => ($grouped['ae_hsd'] ?? 0) + ($grouped['ae_mfo'] ?? 0) + ($grouped['genset_consum_hsd'] ?? 0),
            'EXCESS AE' => ($grouped['bl_ae'] ?? 0) - (($grouped['ae_hsd'] ?? 0) + ($grouped['ae_mfo'] ?? 0) + ($grouped['genset_consum_hsd'] ?? 0)),
            ];

        return $ordered;
    }

    public function show(Request $request)
    {   
        set_time_limit(1200);
        
        if (!$request->filled('report_date')) {
            return view('po.upload', [
                'report14' => null,
                'report16' => null,
                'port_sea_data' => null,
            ]);
        }
        
        $reportDate = $request->input('report_date', date('Y-m-d', strtotime('-1 day')));
        $formattedDate = \Carbon\Carbon::parse($reportDate)->format('d/m/Y');

        $basePayload = [
            "tanggal" => $formattedDate,
        ];

        $reportIds = [14, 16];
        $allReports = [];

        $baselines = \App\Models\FuelBaseline::all()->keyBy('vessel_id');

        foreach ($reportIds as $reportId) {
            $payload = $basePayload;
            $payload['report_id'] = (string)$reportId;

            $response = Http::timeout(120)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->withBody(json_encode($payload), 'application/json')
            ->get('http://nanika.spil.co.id:3021/get-bunker-analysis');

            if (!$response->successful()) {
                return view('po.upload', [
                    'error' => 'Gagal ambil data API (report_id: '.$reportId.', status: '.$response->status().')',
                    'report14' => [],
                    'report16' => [],
                ]);
            }

            $data = $response->json();
            $reports = $data['data'] ?? [];

            $normalized = array_map(fn($r) => $this->reorderReport($r, $baselines), $reports);

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

                // gabungkan port dan sea
                foreach ($port as $key => $val) {
                    $merged[$key] = ['port' => $val, 'sea' => $sea_index[$vesselId][$key] ?? null];
                }

                // tambahkan key yang hanya ada di sea
                foreach ($sea_index[$vesselId] as $key => $val) {
                    if (!isset($merged[$key])) {
                        $merged[$key] = ['port' => null, 'sea' => $val];
                    }
                }

                $port_sea_data[] = $merged;
            }
        }

        $greenColumns = ['BL M/E', 'BL A/E (L/Day)', 'BL L/NM'];
        $analysisColumns = ['SELISIH ME Maneuvering', 'EXCESS AE', 'EXCESS ME MFO L/NM (%)'];

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

        $headers_port = array_filter(array_keys($colored_port[0] ?? []), fn($k) => $k !== '_row_class');
        $headers_sea  = array_filter(array_keys($colored_sea[0] ?? []), fn($k) => $k !== '_row_class');

        session(['port_anomaly' => $colored_port, 'sea_anomaly' => $colored_sea, 'port_sea_data' => $port_sea_data]);

        return view('po.upload', [
            'headers_port' => $headers_port,
            'report14' => $colored_port,
            'headers_sea' => $headers_sea,
            'report16' => $colored_sea,
            'port_sea_header' => array_keys($port_sea_data[0] ?? []),
            'port_sea_data' => $port_sea_data
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
                    $bl_me_val = $row['BL M/E']['value'] ?? 0;
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

                case 'EXCESS ME MFO L/NM (%)':
                    $htmlBody .= "<br>Terdapat <b>pemakaian ME MFO berlebih</b>.<br>";
                    $htmlBody .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; text-align: center; margin-top: 20px;">';
                    $htmlBody .= '<thead><tr>
                        <th>Steam Distance (Miles)</th>
                        <th>Steam Time (Hour)</th>
                        <th>M/E MFO</th>
                        <th>BL L/NM</th>
                        <th>L/NM</th>
                        <th>Excess ME MFO L/NM (%)</th>
                    </tr></thead><tbody>';

                    $steam_distance_val = $row['STEAM. DIST.']['value'] ?? 0;
                    $steam_time_val = $row['STEAM TIME (HOUR : MINUTE)']['value'] ?? 0;
                    $me_mfo_val = $row['M/E MFO']['value'] ?? 0;
                    $bl_ln_val = $row['BL L/NM']['value'] ?? 0;
                    $lnm_val = $row['L/NM']['value'] ?? 0;
                    $excess_me_mfo_lnm_val = $row['EXCESS ME MFO L/NM (%)']['value'] ?? 0;

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

        return redirect('/consumption-analysis/statis')
            ->with('success_email', 'All e-mails sent successfully.');
    }
}