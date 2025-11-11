<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use DateTime;

class SummaryController extends Controller
{
    function reorderReport($grouped) {
        $bl_l_nm_FilePath = storage_path('app\BL for Analysis.xlsx');
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

        $bl_l_nm = $bl_l_nm_map[$vesselKey]['bl_l_nm'] ?? 10;
        
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

            'LOAD A/E 1 (KW)' => $grouped['load_ae_1'] ?? null,
            'LOAD A/E 2 (KW)' => $grouped['load_ae_2'] ?? null,
            'LOAD A/E 3 (KW)' => $grouped['load_ae_3'] ?? null,
            'LOAD A/E 4 (KW)' => $grouped['load_ae_4'] ?? null,

            'REEFER 20"' => $grouped['reefer20'] ?? null,
            'CRANE DURATION' => $grouped['crane_duration'] ?? null,
            'TOTAL CRANE' => $grouped['total_crane'] ?? null, 
            'AE PARAREL DURATION' => $grouped['ae_pararel_duration'] ?? null,
            'REEFER 40"' => $grouped['reefer40'] ?? null,
            
            'BL M/E' => $grouped['bl_me_hsd'] ?? null,
            'ME Maneuvering Cons. (L/H)' => $grouped['me_manuev_consum'] ?? null,
            'SELISIH ME Maneuvering' => $grouped['selisih'] ?? null,

            'BL L/NM' => $bl_l_nm,

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
        // api
        $reportDate = $request->input('report_date', date('Y-m-d'));
        $formattedDate = \Carbon\Carbon::parse($reportDate)->format('d/m/Y');

        $basePayload = [
            "tanggal" => $formattedDate,
        ];

        $reportIds = [14, 16];
        $allReports = [];

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
                return view('po.planning', [
                    'error' => 'Gagal ambil data API (report_id: '.$reportId.', status: '.$response->status().')',
                    'report14' => [],
                    'report16' => [],
                ]);
            }

            $data = $response->json();
            $reports = $data['data'] ?? [];

            // ambil kolom
            $normalized = array_map([$this, 'reorderReport'], $reports);

            // misahin sea port
            $allReports[$reportId] = $normalized;
        }

        $port_data = $allReports[14] ?? [];
        $sea_data = $allReports[16] ?? [];

        // hapus duplikat di satu data
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

        // hitung jumlah vessel yang operate
        $allVessels = array_merge($port_data, $sea_data);
        $uniqueVessels = count(array_unique(array_map(fn($r) => strtoupper($r['Vessel ID'] ?? ''), $allVessels)));
        $uniqueVesselIds = array_unique(array_map(fn($r) => strtoupper($r['Vessel ID'] ?? ''), $allVessels));

        // hitung vessel per fleet
        $fleetMap = [];
        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/fleet.xlsx'))->getActiveSheet();
        foreach (array_slice($sheet->toArray(null, true, true, true), 1) as $row) {
            $fleet = trim($row['A']);
            $vessel = strtoupper(trim($row['B']));
            if ($fleet && $vessel) $fleetMap[$vessel] = $fleet;
        }

        $fleetCounts = [];
        foreach ($uniqueVesselIds as $vessel) {
            $fleet = $fleetMap[$vessel] ?? 'UNKNOWN';
            $fleetCounts[$fleet] = ($fleetCounts[$fleet] ?? 0) + 1;
        }
        ksort($fleetCounts);

        $calculateSpecificConsumption = function (array $data, array $keys): array {
            $totals = array_fill_keys($keys, 0.0);

            foreach ($data as $row) {
                foreach ($keys as $key) {
                    $value = floatval($row[$key] ?? 0);
                    $totals[$key] += $value;
                }
            }

            return $totals;
        };

        $consumptionKeys = [
            'M/E HSD',
            'M/E MFO',
            'A/E HSD',
            'A/E MFO',
            'BOILER HSD',
            'BOILER MFO',
            'GENSET CONSUMPTION - HSD',
        ];

        $totalConsumption_port = $calculateSpecificConsumption($port_data, $consumptionKeys);
        $totalConsumption_sea  = $calculateSpecificConsumption($sea_data, $consumptionKeys);

        // me hsd tanpa maneuvering
        $countManeuvering = function ($data) {
            $count = 0;
            foreach ($data as $row) {
                $me_hsd = floatval($row['M/E HSD'] ?? 0);
                $man = floatval($row['MANEUVERING TIME (HOURS)'] ?? 0);
                if ($me_hsd > 0 && $man == 0) $count++;
            }
            return $count;
        };

        $getManeuveringDetails = function ($data) {
            $details = [];
            foreach ($data as $row) {
                $me_hsd = floatval($row['M/E HSD'] ?? 0);
                $man = floatval($row['MANEUVERING TIME (HOURS)'] ?? 0);
                $vessel = strtoupper(trim($row['Vessel ID'] ?? ''));
                if ($me_hsd > 0 && $man == 0 && $vessel !== '') {
                    $details[] = [
                        'vessel' => $vessel,
                        'me_hsd' => $me_hsd,
                        'maneuvering' => $man
                    ];
                }
            }

            usort($details, function ($a, $b) {
                return $b['me_hsd'] <=> $a['me_hsd'];
            });

            return $details;
        };

        $count_me_hsd_maneuvering_port = $countManeuvering($port_data);
        $count_me_hsd_maneuvering_sea  = $countManeuvering($sea_data);

        // ae berlebih
        $countExcessTime = function ($data) {
            $count = 0;
            foreach ($data as $row) {
                $ae = floatval($row['AE PARAREL DURATION'] ?? 0);
                $crane = floatval($row['CRANE DURATION'] ?? 0);
                $man = floatval($row['MANEUVERING TIME (HOURS)'] ?? 0);
                if (($ae - $crane - $man) > 3) $count++;
            }
            return $count;
        };

        $getExcessTimeDetails = function ($data) {
            $details = [];
            foreach ($data as $row) {
                $ae = floatval($row['AE PARAREL DURATION'] ?? 0);
                $crane = floatval($row['CRANE DURATION'] ?? 0);
                $man = floatval($row['MANEUVERING TIME (HOURS)'] ?? 0);
                $vessel = strtoupper(($row['Vessel ID'] ?? ''));
                $diff = $ae - $crane - $man;
                if ($diff > 3 && $vessel !== '') {
                    $details[] = [
                        'vessel' => $vessel,
                        'ae_pararel' => $ae,
                        'crane' => $crane,
                        'maneuvering' => $man,
                        'diff' => $diff
                    ];
                }
            }

            usort($details, function ($a, $b) {
                return $b['diff'] <=> $a['diff'];
            });

            return $details;
        };

        $count_time_port = $countExcessTime($port_data);
        $count_time_sea  = $countExcessTime($sea_data);

        // minus
        $countMinusValues = function ($data, $columns) {
            $counts = array_fill_keys($columns, 0);
            foreach ($data as $row) {
                foreach ($columns as $col) {
                    $value = trim($row[$col] ?? '');
                    $numeric = is_numeric($value) ? floatval($value) : null;

                    if ($numeric !== null && $numeric < 0) {
                        $counts[$col]++;
                    }
                }
            }
            return $counts;
        };

        $getMinusDetails = function ($data, $columns) {
            $details = [];
            foreach ($data as $row) {
                $vessel = strtoupper(trim($row['Vessel ID'] ?? ''));

                $me_hsd = floatval($row['M/E HSD'] ?? 0);
                $man = floatval($row['MANEUVERING TIME (HOURS)'] ?? 0);
                $bl_me = floatval($row['BL M/E'] ?? 0);
                $me_mnv = floatval($row['ME Maneuvering Cons. (L/H)'] ?? 0);
                $selisih = floatval($row['SELISIH ME Maneuvering'] ?? 0);

                $ae_mfo = floatval($row['A/E MFO'] ?? 0);
                $ae_hsd = floatval($row['A/E HSD'] ?? 0);
                $genset = floatval($row['GENSET CONSUMPTION - HSD'] ?? 0);
                $bl_ae = floatval($row['BL A/E (L/Day)'] ?? 0);
                $ae_consumption = floatval($row['AE Consumption'] ?? 0);
                $excess_ae = floatval($row['EXCESS AE'] ?? 0);

                $steam_distance = floatval($row['STEAM. DIST.'] ?? 0);
                $steam_time = floatval($row['STEAM TIME (HOUR : MINUTE)'] ?? 0);
                $me_mfo = floatval($row['M/E MFO'] ?? 0);
                $bl_l_nm = floatval($row['BL L/NM'] ?? 0);
                $l_nm = floatval($row['L/NM'] ?? 0);
                $excess_me_mfo_l_nm = floatval($row['EXCESS ME MFO L/NM (%)'] ?? 0);

                if ($vessel === '') continue;

                foreach ($columns as $col) {
                    $value = trim($row[$col] ?? '');

                    if ($col == 'SELISIH ME Maneuvering') {
                        if ($selisih !== null && $selisih < 0) {
                            $details[$col][] = [
                                'vessel' => $vessel,
                                'me_hsd' => $me_hsd,
                                'maneuvering' => $man,
                                'bl_me' => $bl_me,
                                'me_mnv' => $me_mnv,
                                'selisih' => $selisih
                            ];
                        }
                    }
                    else if ($col == 'EXCESS AE') {
                        if ($excess_ae !== null && $excess_ae < 0) {
                            $details[$col][] = [
                                'vessel' => $vessel,
                                'ae_mfo' => $ae_mfo,
                                'ae_hsd' => $ae_hsd,
                                'genset' => $genset,
                                'bl_ae' => $bl_ae,
                                'ae_consumption' => $ae_consumption,
                                'excess_ae' => $excess_ae
                            ];
                        }
                    }
                    else if ($col == 'EXCESS ME MFO L/NM (%)') {
                        if ($excess_me_mfo_l_nm !== null && $excess_me_mfo_l_nm < 0) {
                            $details[$col][] = [
                                'vessel' => $vessel,
                                'steam_distance' => $steam_distance,
                                'steam_time' => $steam_time,
                                'me_mfo' => $me_mfo,
                                'bl_l_nm' => $bl_l_nm,
                                'l_nm' => $l_nm,
                                'excess_me_mfo_l_nm' => $excess_me_mfo_l_nm
                            ];
                        }
                    }
                }
            }

            return $details;
        };

        $minusColumns = ['SELISIH ME Maneuvering', 'EXCESS AE', 'EXCESS ME MFO L/NM (%)'];

        $count_minus_port = $countMinusValues($port_data, $minusColumns);
        $count_minus_sea  = $countMinusValues($sea_data, $minusColumns);

        $details_minus_port = $getMinusDetails($port_data, $minusColumns);
        $details_minus_sea  = $getMinusDetails($sea_data, $minusColumns);

        if (!empty($details_minus_port['SELISIH ME Maneuvering'])) {
            usort($details_minus_port['SELISIH ME Maneuvering'], function ($a, $b) {
                return ($b['selisih'] ?? 0) <=> ($a['selisih'] ?? 0);
            });
        }

        if (!empty($details_minus_port['EXCESS AE'])) {
            usort($details_minus_port['EXCESS AE'], function ($a, $b) {
                return ($b['excess_ae'] ?? 0) <=> ($a['excess_ae'] ?? 0);
            });
        }
        
        if (!empty($details_minus_sea['SELISIH ME Maneuvering'])) {
            usort($details_minus_sea['SELISIH ME Maneuvering'], function ($a, $b) {
                return ($b['selisih'] ?? 0) <=> ($a['selisih'] ?? 0);
            });
        }

        if (!empty($details_minus_sea['EXCESS AE'])) {
            usort($details_minus_sea['EXCESS AE'], function ($a, $b) {
                return ($b['excess_ae'] ?? 0) <=> ($a['excess_ae'] ?? 0);
            });
        }

        if (!empty($details_minus_sea['EXCESS ME MFO L/NM (%)'])) {
            usort($details_minus_sea['EXCESS ME MFO L/NM (%)'], function ($a, $b) {
                return ($a['excess_me_mfo_l_nm'] ?? 0) <=> ($b['excess_me_mfo_l_nm'] ?? 0);
            });
        }

        session([
            'details_me_hsd_maneuvering_port' => $getManeuveringDetails($port_data),
            'details_me_hsd_maneuvering_sea'  => $getManeuveringDetails($sea_data),
            'details_time_port'               => $getExcessTimeDetails($port_data),
            'details_time_sea'                => $getExcessTimeDetails($sea_data),
            'details_minus_port'              => $details_minus_port,
            'details_minus_sea'               => $details_minus_sea,
        ]);

        return view('dashboard', compact(
            'uniqueVessels',
            'fleetCounts',
            'totalConsumption_port',
            'totalConsumption_sea',
            'count_me_hsd_maneuvering_port',
            'count_me_hsd_maneuvering_sea',
            'count_time_port',
            'count_time_sea',
            'count_minus_port',
            'count_minus_sea',
        ));
    }
}