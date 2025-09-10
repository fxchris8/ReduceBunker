<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class SummaryController extends Controller
{
    public function upload(Request $request)
    {
        // report data
        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());

        $sheet = $spreadsheet->getSheetByName('At PORT');
        if ($sheet) {
            $cellC7 = $sheet->getCell('C7')->getValue();
            $tanggal = explode(' ', $cellC7)[0];
        }

        $sheetNames = ['At SEA', 'At PORT'];
        $vesselSummary = [
            'At SEA' => [],
            'At PORT' => [],
        ];

        $normalizeHeader = function ($header) {
            $header = strtoupper(trim($header));
            $header = preg_replace('/\s+/', ' ', $header);
            $header = preg_replace('/\(\s+/', '(', $header);
            $header = preg_replace('/\s+\)/', ')', $header);
            return $header;
        };

        foreach ($sheetNames as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (!$sheet) continue;

            $data = $sheet->toArray(null, true, true, true);
            $headerRows = array_slice($data, 3, 3);
            $tableRows  = array_slice($data, 6);

            $vesselKey = array_search('VESSEL', array_map('strtoupper', $headerRows[0]));

            if ($vesselKey !== false) {
                $vesselSet = [];
                foreach ($tableRows as $row) {
                    $vesselName = strtoupper(trim($row[$vesselKey] ?? ''));
                    if ($vesselName !== '' && !in_array($vesselName, $vesselSet)) {
                        $vesselSet[] = $vesselName;
                    }
                }

                //summary
                $vesselSummary[$sheetName] = $vesselSet;

                if ($sheetName === 'At PORT') {
                    $details_me_hsd_maneuvering_port = [];
                    $details_time_port = [];
                    $details_minus_port = [];

                    $consumptionKeys_port = [
                        'ME HSD',
                        'ME MFO',
                        'A/E HSD',
                        'A/E MFO',
                        'BOILER HSD',
                        'BOILER MFO',
                        'GENSET CONSUMPTION - HSD'
                    ];

                    $normalizedHeader = array_map($normalizeHeader, $headerRows[0]);

                    //index
                    $consumptionIndexes_port = [];
                    foreach ($consumptionKeys_port as $key) {
                        $upperKey = strtoupper($key);
                        $index = array_search($upperKey, $normalizedHeader);
                        if ($index !== false) {
                            $consumptionIndexes_port[$key] = $index;
                        }
                    }

                    $totalConsumption_port = array_fill_keys($consumptionKeys_port, 0.0);

                    // non duplicated 1
                    $seenVessels_port = [];
                    foreach ($tableRows as $row) {
                        $vesselName = strtoupper(trim($row[$vesselKey] ?? ''));
                        if ($vesselName === '' || in_array($vesselName, $seenVessels_port)) continue;

                        $seenVessels_port[] = $vesselName;

                        foreach ($consumptionIndexes_port as $key => $colIndex) {
                            $rawValue = $row[$colIndex] ?? '';
                            $value = is_numeric($rawValue) ? floatval($rawValue) : 0.0;
                            $totalConsumption_port[$key] += $value;
                        }
                    }

                    //minus
                    $minusColumns_port = [
                        'SELISIH',
                        'EXCESS AE'
                    ];

                    $minusIndexes_port = [];
                    $hasil_analisis = array_map($normalizeHeader, $headerRows[2]);

                    //index
                    foreach ($minusColumns_port as $key) {
                        $upperKey = strtoupper($key);
                        $index = array_search($upperKey, $hasil_analisis);
                        if ($index !== false) {
                            $minusIndexes_port[$key] = $index;
                        }
                    }

                    // non duplicated 2
                    $minusCounts_port = array_fill_keys($minusColumns_port, 0);
                    $seenMinusVessels_port = [];

                    // non duplicated 3
                    $count_me_hsd_maneuvering_port = [];
                    $vessels_me_hsd_manuvering_port = [];

                    // non duplicated 4
                    $count_time_port = [];
                    $vessels_time_port = [];

                    $me_hsd_key         = array_search('ME HSD', $normalizedHeader);
                    $ae_pararel_key     = array_search('AE PARAREL DURATION', $normalizedHeader);
                    $crane_key          = array_search('CRANE DURATION', $normalizedHeader);
                    $maneuvering_key    = array_search('MANEUVERING TIME (HOUR : MINUTE)', $normalizedHeader);
                    $me_mfo_key         = array_search('ME MFO', $normalizedHeader);
                    $ae_mfo_key         = array_search('A/E MFO', $normalizedHeader);
                    $ae_hsd_key         = array_search('A/E HSD', $normalizedHeader);
                    $genset_key         = array_search('GENSET CONSUMPTION - HSD', $normalizedHeader);
                    $bl_me_key          = array_search('BL ME HSD (L/H)', $hasil_analisis);
                    $me_mnv_key         = array_search('ME MANEUV CONSM. (L/H)', $hasil_analisis);
                    $selisih_key        = array_search('SELISIH', $hasil_analisis);
                    $bl_ae_key              = array_search('BL AE (L/DAY)', $hasil_analisis);
                    $excess_ae_key          = array_search('EXCESS AE', $hasil_analisis);

                    foreach ($tableRows as $row) {
                        $vesselName = strtoupper(trim($row[$vesselKey] ?? ''));

                        //minus
                        if ($vesselName === '' || in_array($vesselName, $seenMinusVessels_port)) continue;

                        $seenMinusVessels_port[] = $vesselName;

                        foreach ($minusIndexes_port as $key => $colIndex) {
                            $rawValue = $row[$colIndex] ?? '';

                            if (str_contains($rawValue, '%')) {
                                $numericValue = floatval(str_replace('%', '', $rawValue));
                            } elseif (trim($rawValue) === '-') {
                                $rawValue = '0';
                                $numericValue = 0;
                            } elseif (str_contains($rawValue, '#DIV/0!')) {
                                $rawValue = '-';
                                $numericValue = 0;
                            } else {
                                $numericValue = $rawValue;
                            }

                            $value = is_numeric($numericValue) ? floatval($numericValue) : null;

                            if ($value !== null && $value < 0) {
                                $minusCounts_port[$key]++;

                                $me_mfo              = $row[$me_mfo_key] ?? '';
                                $ae_mfo              = $row[$ae_mfo_key] ?? '';
                                $ae_hsd              = $row[$ae_hsd_key] ?? '';
                                $genset_hsd          = $row[$genset_key] ?? '';

                                $bl_me               = ceil(floatval($row[$bl_me_key] ?? '') * 100) / 100;

                                $me_mnv              = $row[$me_mnv_key] ?? '';
                                
                                $selisih             = ceil(floatval($row[$selisih_key] ?? '') * 100) / 100;

                                $bl_ae               = $row[$bl_ae_key] ?? '';
                                $excess_ae           = $row[$excess_ae_key] ?? '';

                                $total_ae            = $ae_mfo + $ae_hsd + $genset_hsd;

                                $details_minus_port[$key][] = [
                                    'vessel'              => $vesselName,
                                    'me_hsd'              => $me_hsd,
                                    'ae_pararel_duration' => $ae_pararel_duration,
                                    'crane_duration'      => $crane_duration,
                                    'maneuvering_time'    => $maneuvering_time,
                                    'me_mfo'              => $me_mfo,
                                    'ae_mfo'              => $ae_mfo,
                                    'ae_hsd'              => $ae_hsd,
                                    'genset_hsd'          => $genset_hsd,
                                    'bl_me'               => $bl_me,
                                    'me_mnv'              => $me_mnv,
                                    'selisih'             => $selisih,
                                    'bl_ae'               => $bl_ae,
                                    'excess_ae'           => $excess_ae,
                                    'total_ae'            => $total_ae,
                                ];
                            }
                        }

                        // me hsd maneuvering
                        if ($vesselName === '' || in_array($vesselName, $vessels_me_hsd_manuvering_port)) continue;
                        
                        $me_hsd_val = floatval($row[$me_hsd_key] ?? '');
                        $maneuvering_val = floatval($row[$maneuvering_key] ?? '');

                        if ($me_hsd_val !== 0.0 && $maneuvering_val == 0.0) {
                            $vessels_me_hsd_manuvering_port[] = $vesselName;

                            $details_me_hsd_maneuvering_port[] = [
                                'vessel' => $vesselName,
                                'me_hsd' => $me_hsd_val,
                                'maneuvering' => $maneuvering_val
                            ];
                        }

                        // time
                        if ($vesselName === '' || in_array($vesselName, $vessels_time_port)) continue;
                        
                        $ae_pararel_val    = $row[$ae_pararel_key] ?? '';
                        $crane_val         = $row[$crane_key] ?? '';
                        $maneuvering_val   = $row[$maneuvering_key] ?? '';

                        $diff = $ae_pararel_val - $crane_val - $maneuvering_val;

                        if ($diff > 3) {
                            $vessels_time_port[] = $vesselName;

                            $details_time_port[] = [
                                'vessel' => $vesselName,
                                'ae_pararel' => $ae_pararel_val,
                                'crane' => $crane_val,
                                'maneuvering' => $maneuvering_val,
                                'diff' => $diff
                            ];
                        }
                    }

                $count_me_hsd_maneuvering_port['me_hsd_maneuvering'] = count($vessels_me_hsd_manuvering_port);
                $count_time_port = count($vessels_time_port);

                } elseif ($sheetName === 'At SEA') {
                    $details_me_hsd_maneuvering_sea = [];
                    $details_time_sea = [];
                    $details_minus_sea = [];

                    $consumptionKeys_sea = [
                        'ME HSD',
                        'ME MFO',
                        'A/E HSD',
                        'AE MFO',
                        'BOILER HSD',
                        'BOILER MFO',
                        'GENSET CONSUMPTION - HSD'
                    ];

                    $normalizedHeader = array_map($normalizeHeader, $headerRows[0]);

                    $consumptionIndexes_sea = [];
                    foreach ($consumptionKeys_sea as $key) {
                        $upperKey = strtoupper($key);
                        $index = array_search($upperKey, $normalizedHeader);
                        if ($index !== false) {
                            $consumptionIndexes_sea[$key] = $index;
                        }
                    }

                    $totalConsumption_sea = array_fill_keys($consumptionKeys_sea, 0.0);

                    $seenVessels_sea = [];
                    foreach ($tableRows as $row) {
                        $vesselName = strtoupper(trim($row[$vesselKey] ?? ''));
                        if ($vesselName === '' || in_array($vesselName, $seenVessels_sea)) continue;

                        $seenVessels_sea[] = $vesselName;

                        foreach ($consumptionIndexes_sea as $key => $colIndex) {
                            $rawValue = $row[$colIndex] ?? '';
                            $value = is_numeric($rawValue) ? floatval($rawValue) : 0.0;
                            $totalConsumption_sea[$key] += $value;
                        }
                    }

                    $minusColumns_sea = [
                        'SELISIH',
                        'EXCESS ME MFO L/NM (%)',
                        'EXCESS AE'
                    ];

                    $minusIndexes_sea = [];
                    $hasil_analisis = array_map($normalizeHeader, $headerRows[2]);

                    foreach ($minusColumns_sea as $key) {
                        $upperKey = strtoupper($key);
                        $index = array_search($upperKey, $hasil_analisis);
                        if ($index !== false) {
                            $minusIndexes_sea[$key] = $index;
                        }
                    }

                    $minusCounts_sea = array_fill_keys($minusColumns_sea, 0);
                    $seenMinusVessels_sea = [];

                    // non duplicated 3
                    $count_me_hsd_maneuvering_sea = [];
                    $vessels_me_hsd_manuvering_sea = [];

                    // non duplicated 4
                    $count_time_sea = [];
                    $vessels_time_sea = [];

                    $me_hsd_key         = array_search('ME HSD', $normalizedHeader);
                    $ae_pararel_key     = array_search('AE PARAREL DURATION', $normalizedHeader);
                    $crane_key          = array_search('CRANE DURATION', $normalizedHeader);
                    $maneuvering_key    = array_search('MANUVERING TIME', $normalizedHeader);
                    $steam_dist_key     = array_search('STEAM DISTANCE (MILES)', $normalizedHeader);
                    $steam_time_key     = array_search('STEAM TIME (HOUR)', $normalizedHeader);
                    $me_mfo_key         = array_search('ME MFO', $normalizedHeader);
                    $ae_mfo_key         = array_search('AE MFO', $normalizedHeader);
                    $ae_hsd_key         = array_search('A/E HSD', $normalizedHeader);
                    $genset_key         = array_search('GENSET CONSUMPTION - HSD', $normalizedHeader);
                    $bl_me_key          = array_search('BL ME', $hasil_analisis);
                    $me_mnv_key         = array_search('ME MANEUVERING CONSM. (L/H)', $hasil_analisis);
                    $selisih_key        = array_search('SELISIH', $hasil_analisis);
                    $lnm_key                = array_search('L/NM', $hasil_analisis);
                    $bl_lnm_key             = array_search('BL L/NM', $hasil_analisis);
                    $excess_me_mfo_key      = array_search('EXCESS ME MFO L/NM (%)', $hasil_analisis);
                    $bl_ae_key              = array_search('BL AE (L/DAY)', $hasil_analisis);
                    $excess_ae_key          = array_search('EXCESS AE', $hasil_analisis);

                    foreach ($tableRows as $row) {
                        $vesselName = strtoupper(trim($row[$vesselKey] ?? ''));
                        
                        if ($vesselName === '' || in_array($vesselName, $seenMinusVessels_sea)) continue;

                        $seenMinusVessels_sea[] = $vesselName;

                        foreach ($minusIndexes_sea as $key => $colIndex) {
                            $rawValue = $row[$colIndex] ?? '';

                            if (str_contains($rawValue, '%')) {
                                $numericValue = floatval(str_replace('%', '', $rawValue));
                            } elseif (trim($rawValue) === '-') {
                                $rawValue = '0';
                                $numericValue = 0;
                            } elseif (str_contains($rawValue, '#DIV/0!')) {
                                $rawValue = '-';
                                $numericValue = 0;
                            } else {
                                $numericValue = $rawValue;
                            }

                            $value = is_numeric($numericValue) ? floatval($numericValue) : null;

                            if ($value !== null && $value < 0) {
                                $minusCounts_sea[$key]++;

                                $me_hsd              = $row[$me_hsd_key] ?? '';
                                $ae_pararel_duration = $row[$ae_pararel_key] ?? '';
                                $crane_duration      = $row[$crane_key] ?? '';
                                $maneuvering_time    = $row[$maneuvering_key] ?? '';

                                $steam_distance      = ceil(floatval($row[$steam_dist_key] ?? '') * 100) / 100;

                                $steam_time          = $row[$steam_time_key] ?? '';
                                $me_mfo              = $row[$me_mfo_key] ?? '';
                                $ae_mfo              = $row[$ae_mfo_key] ?? '';
                                $ae_hsd              = $row[$ae_hsd_key] ?? '';
                                $genset_hsd          = $row[$genset_key] ?? '';

                                $bl_me               = ceil(floatval($row[$bl_me_key] ?? '') * 100) / 100;

                                $me_mnv              = $row[$me_mnv_key] ?? '';

                                $selisih             = ceil(floatval($row[$selisih_key] ?? '') * 100) / 100;

                                $lnm                 = ceil(floatval($row[$lnm_key] ?? '') * 100) / 100;

                                $bl_lnm              = $row[$bl_lnm_key] ?? '';
                                $excess_me_mfo       = $row[$excess_me_mfo_key] ?? '';
                                $bl_ae               = $row[$bl_ae_key] ?? '';
                                $excess_ae           = $row[$excess_ae_key] ?? '';

                                $total_ae            = $ae_mfo + $ae_hsd + $genset_hsd;

                                $details_minus_sea[$key][] = [
                                    'vessel'              => $vesselName,
                                    'me_hsd'              => $me_hsd,
                                    'ae_pararel_duration' => $ae_pararel_duration,
                                    'crane_duration'      => $crane_duration,
                                    'maneuvering_time'    => $maneuvering_time,
                                    'steam_distance'      => $steam_distance, 
                                    'steam_time'          => $steam_time,
                                    'me_mfo'              => $me_mfo,
                                    'ae_mfo'              => $ae_mfo,
                                    'ae_hsd'              => $ae_hsd,
                                    'genset_hsd'          => $genset_hsd,
                                    'bl_me'               => $bl_me,
                                    'me_mnv'              => $me_mnv,
                                    'selisih'             => $selisih,
                                    'lnm'                 => $lnm,
                                    'bl_lnm'              => $bl_lnm,
                                    'excess_me_mfo'       => $excess_me_mfo,
                                    'bl_ae'               => $bl_ae,
                                    'excess_ae'           => $excess_ae,
                                    'total_ae'            => $total_ae,
                                ];
                            }
                        }

                        // me hsd maneuvering
                        if ($vesselName === '' || in_array($vesselName, $vessels_me_hsd_manuvering_sea)) continue;
                        
                        $me_hsd_val = floatval($row[$me_hsd_key] ?? '');
                        $maneuvering_val = floatval($row[$maneuvering_key] ?? '');

                        if ($me_hsd_val !== 0.0 && $maneuvering_val == 0.0) {
                            $vessels_me_hsd_manuvering_sea[] = $vesselName;

                            $details_me_hsd_maneuvering_sea[] = [
                                'vessel' => $vesselName,
                                'me_hsd' => $me_hsd_val,
                                'maneuvering' => $maneuvering_val
                            ];
                        }

                        // time
                        if ($vesselName === '' || in_array($vesselName, $vessels_time_sea)) continue;
                        
                        $ae_pararel_val    = $row[$ae_pararel_key] ?? '';
                        $crane_val         = $row[$crane_key] ?? '';
                        $maneuvering_val   = $row[$maneuvering_key] ?? '';

                        $diff = $ae_pararel_val - $crane_val - $maneuvering_val;

                        if ($diff > 3) {
                            $vessels_time_sea[] = $vesselName;

                            $details_time_sea[] = [
                                'vessel' => $vesselName,
                                'ae_pararel' => $ae_pararel_val,
                                'crane' => $crane_val,
                                'maneuvering' => $maneuvering_val,
                                'diff' => $diff
                            ];
                        }
                    }
                }
                $count_me_hsd_maneuvering_sea['me_hsd_maneuvering'] = count($vessels_me_hsd_manuvering_sea);
                $count_time_sea = count($vessels_time_sea);
            }
        }

        $hsdData_port = [];
        $mfoData_port = [];

        foreach ($totalConsumption_port as $key => $value) {
            if (Str::contains($key, 'HSD')) {
                $hsdData_port[$key] = $value;
            } elseif (Str::contains($key, 'MFO')) {
                $mfoData_port[$key] = $value;
            }
        }

        $hsdData_sea = [];
        $mfoData_sea = [];

        foreach ($totalConsumption_sea as $key => $value) {
            if (Str::contains($key, 'HSD')) {
                $hsdData_sea[$key] = $value;
            } elseif (Str::contains($key, 'MFO')) {
                $mfoData_sea[$key] = $value;
            }
        }

        if (isset($totalConsumption_sea['AE MFO'])) {
            $value = $totalConsumption_sea['AE MFO'];
            unset($totalConsumption_sea['AE MFO']);

            $newArray = [];
            foreach ($totalConsumption_sea as $key => $val) {
                $newArray[$key] = $val;
                if ($key === 'A/E HSD') {
                    $newArray['A/E MFO'] = $value;
                }
            }
            $totalConsumption_sea = $newArray;
        }

        if (isset($minusCounts_sea['EXCESS AE'])) {
            $selisih_value = $minusCounts_sea['SELISIH'];
            $excess_me_value = $minusCounts_sea['EXCESS ME MFO L/NM (%)'];
            $excess_ae_value = $minusCounts_sea['EXCESS AE'];
            
            unset($minusCounts_sea['SELISIH']);
            unset($minusCounts_sea['EXCESS ME MFO L/NM (%)']);
            unset($minusCounts_sea['EXCESS AE']);

            $newArray = [];
            $newArray['ME HSD Consumption For Maneuvering > BL'] = $selisih_value;
            $newArray['ME MFO Consumption > BL'] = $excess_me_value;
            $newArray['A/E Consumption > BL'] = $excess_ae_value;
            
            $minusCounts_sea = $newArray;
        }

        if (isset($minusCounts_port['EXCESS AE'])) {
            $selisih_value = $minusCounts_port['SELISIH'];
            $excess_ae_value = $minusCounts_port['EXCESS AE'];
            
            unset($minusCounts_port['SELISIH']);
            unset($minusCounts_port['EXCESS AE']);

            $newArray = [];
            $newArray['ME HSD Maneuvering Consumption > BL'] = $selisih_value;
            $newArray['A/E Consumption > BL'] = $excess_ae_value;
            
            $minusCounts_port = $newArray;
        }

        // $atSeaCount     = count($vesselSummary['At SEA']);
        // $atPortCount    = count($vesselSummary['At PORT']);
        $uniqueVessels  = count(array_unique(array_merge(
            $vesselSummary['At SEA'],
            $vesselSummary['At PORT']
        )));

        $fleetFilePath = storage_path('app\fleet.xlsx');
        $spreadsheetFleet = IOFactory::load($fleetFilePath);
        $sheetFleet = $spreadsheetFleet->getActiveSheet();
        $fleetData = $sheetFleet->toArray(null, true, true, true);

        $vesselFleets = [];
        foreach (array_slice($fleetData, 1) as $row) {
            $fleet = trim($row['A']); 
            $vessel = trim($row['B']); 
            if ($fleet && $vessel) {
                $vesselFleets[strtoupper($vessel)] = $fleet;
            }
        }

        $allFleetTypes = array_unique(array_map('trim', array_column(array_slice($fleetData, 1), 'A')));

        $allVessels = array_unique(array_map('strtoupper', array_merge(
            $vesselSummary['At SEA'],
            $vesselSummary['At PORT']
        )));

        $fleetCounts = [];
        foreach ($allFleetTypes as $fleetType) {
            if ($fleetType !== '') {
                $fleetCounts[$fleetType] = 0;
            }
        }

        foreach ($allVessels as $vesselName) {
            $fleetName = $vesselFleets[$vesselName] ?? 'UNKNOWN';
            if (!isset($fleetCounts[$fleetName])) {
                $fleetCounts[$fleetName] = 0; 
            }
            $fleetCounts[$fleetName]++;
        }

        ksort($fleetCounts);

        if (!empty($details_me_hsd_maneuvering_port) && is_array($details_me_hsd_maneuvering_port[0] ?? null)) {
            usort($details_me_hsd_maneuvering_port, function ($a, $b) {
                return ($b['me_hsd'] ?? 0) <=> ($a['me_hsd'] ?? 0);
            });
        }

        if (!empty($details_me_hsd_maneuvering_sea) && is_array($details_me_hsd_maneuvering_sea[0] ?? null)) {
            usort($details_me_hsd_maneuvering_sea, function ($a, $b) {
                return ($b['me_hsd'] ?? 0) <=> ($a['me_hsd'] ?? 0);
            });
        }

        if (!empty($details_time_port) && is_array($details_time_port[0] ?? null)) {
            usort($details_time_port, function ($a, $b) {
                return ($b['diff'] ?? 0) <=> ($a['diff'] ?? 0);
            });
        }

        if (!empty($details_time_sea) && is_array($details_time_sea[0] ?? null)) {
            usort($details_time_sea, function ($a, $b) {
                return ($b['diff'] ?? 0) <=> ($a['diff'] ?? 0);
            });
        }

        session([
            'details_me_hsd_maneuvering_port' => $details_me_hsd_maneuvering_port,
            'details_time_port' => $details_time_port,
            'details_me_hsd_maneuvering_sea' => $details_me_hsd_maneuvering_sea,
            'details_time_sea' => $details_time_sea,
            'details_minus_port' => $details_minus_port,
            'details_minus_sea' => $details_minus_sea,
        ]);

        return view('dashboard', compact(
            'tanggal',
            'uniqueVessels',
            'fleetCounts',
            'hsdData_port',
            'mfoData_port',
            'hsdData_sea',
            'mfoData_sea',
            'minusCounts_port',
            'minusCounts_sea',
            'count_me_hsd_maneuvering_port',
            'count_time_port',
            'count_me_hsd_maneuvering_sea',
            'count_time_sea',
        ));
    }

    public function show()
    {
        session()->reflash();
        return view('dashboard');
    }

    public function summaryAnalysis()
    {
        $count_me_hsd_maneuvering_port = session('count_me_hsd_maneuvering_port', []);
        $count_time_port = session('count_time_port', []);
        
        return view('dashboard', compact('count_me_hsd_maneuvering_port', 'count_time_port'));
    }

}