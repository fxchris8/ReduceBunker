<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Mail;

class UploadController extends Controller
{
    public function upload(Request $request)
    {
        $location = $request->input('location');
        $file = $request->file('file');

        Log::info('Upload dimulai', [
            'filename' => $file->getClientOriginalName(),
            'location' => $location,
        ]);


        $spreadsheet = IOFactory::load($file->getPathname());

        $sheetName = $location === 'port' ? 'At PORT' : ($location === 'sea' ? 'At SEA' : null);

        if (!$sheetName || !$spreadsheet->sheetNameExists($sheetName)) {
            return back()->withErrors(['Sheet tidak ditemukan']);
        }

        $sheet = $spreadsheet->getSheetByName($sheetName);
        $data = $sheet->toArray(null, true, true, true);
        $headerRows = array_slice($data, 3, 3);
        $tableRows  = array_slice($data, 6);

        $normalizeHeader = function ($header) {
            $header = strtoupper(trim($header));
            $header = preg_replace('/\s+/', ' ', $header);
            $header = preg_replace('/\(\s+/', '(', $header);
            $header = preg_replace('/\s+\)/', ')', $header);
            return $header;
        };

        $vesselKey = array_search('VESSEL', array_map($normalizeHeader, $headerRows[0]));
        $me_hsd = array_search('ME HSD', array_map($normalizeHeader, $headerRows[0]));
        $ae_pararel = array_search('AE PARAREL DURATION', array_map($normalizeHeader, $headerRows[0]));
        $crane_duration = array_search('CRANE DURATION', array_map($normalizeHeader, $headerRows[0]));


        $targetHeaders = ['MANEUVERING TIME (HOUR : MINUTE)', 'MANUVERING TIME'];
        $normalizedHeaders = array_map($normalizeHeader, $headerRows[0]);

        $maneuvering_time_index = null;
        foreach ($targetHeaders as $target) {
            $index = array_search(strtoupper($target), $normalizedHeaders);
            if ($index !== false) {
                $maneuvering_time_index = $index;
                break;
            }
        }


        foreach ($tableRows as $rowIndex => $row) {
            $vesselName = $row[$vesselKey];
            $negativeEntries = [];
            $hasNegative = false;
            $color = [];

            if ($sheetName === 'At PORT') {
                $posCol = array_search('POS', array_map($normalizeHeader, $headerRows[0]));
                $depCol = null;
                $desCol = null;
            }
            elseif ($sheetName === 'At SEA') {
                $depCol = array_search('DEPARTURE', array_map($normalizeHeader, $headerRows[0]));
                $desCol = array_search('DESTINATION', array_map($normalizeHeader, $headerRows[0]));
                $posCol = null;
            }
            
            foreach ($row as $colKey => $cell) {
                if (str_contains($cell, '%')) {
                    $numericValue = floatval(str_replace('%', '', $cell));
                } elseif (trim($cell) === '-') {
                    $cell = '0';
                    $numericValue = 0;
                } elseif (str_contains($cell, '#DIV/0!')) {
                    $cell = '-';
                    $numericValue = 0;
                } elseif (is_numeric($cell)) {
                    $numericValue = $cell;
                    $cell = ceil(floatval($cell) * 100) / 100;
                } else {
                    $numericValue = $cell;
                }

                $row[$colKey] = $cell;
                
                $columnName = $headerRows[2][$colKey] ?? null;

                if (is_numeric($numericValue) && $numericValue < 0 && !empty($columnName)) {
                    $hasNegative = true;
                    $negativeEntries[] = "{$columnName}: {$numericValue}";
                    $color[$colKey] = 'red';
                }
            }

            $dateKey                  = array_search('DATE', array_map($normalizeHeader, $headerRows[0]));
            $load_ae1_key             = array_search('LOAD AE 1 (KW)', array_map($normalizeHeader, $headerRows[0]));
            $load_ae2_key             = array_search('LOAD AE 2 (KW)', array_map($normalizeHeader, $headerRows[0]));
            $load_ae3_key             = array_search('LOAD AE 3 (KW)', array_map($normalizeHeader, $headerRows[0]));
            $load_ae4_key             = array_search('LOAD AE 4 (KW)', array_map($normalizeHeader, $headerRows[0]));
            $reefer_20_key            = array_search('REEFER 20"', array_map($normalizeHeader, $headerRows[0]));
            $reefer_40_key            = array_search('REEFER 40"', array_map($normalizeHeader, $headerRows[0]));
            
            $date_val              = $row[$dateKey] ?? '';
            $tanggal               = explode(' ', $date_val)[0];

            $load_ae1_val          = $row[$load_ae1_key] ?? '';
            $load_ae2_val          = $row[$load_ae2_key] ?? '';
            $load_ae3_val          = $row[$load_ae3_key] ?? '';
            $load_ae4_val          = $row[$load_ae4_key] ?? '';
            $reefer_20_val         = $row[$reefer_20_key] ?? '';
            $reefer_40_val         = $row[$reefer_40_key] ?? '';

            $total_reefer = $reefer_20_val + $reefer_40_val;

            $loadAeValues = [
                (float) ($row[$load_ae1_key] ?? 0),
                (float) ($row[$load_ae2_key] ?? 0),
                (float) ($row[$load_ae3_key] ?? 0),
                (float) ($row[$load_ae4_key] ?? 0),
            ];

            $load_ae_count = count(array_filter($loadAeValues, fn($val) => $val > 0));

            $normalizedHeaders = array_map($normalizeHeader, $headerRows[2]);
            $targetHeaders = ['AE 1 RF', 'REEFER AE 1'];

            $reefer_ae_1_key = null;

            foreach ($normalizedHeaders as $index => $header) {
                if (in_array($header, array_map($normalizeHeader, $targetHeaders))) {
                    $reefer_ae_1_key = $index;
                    break;
                }
            }

            $reefer_ae_1_val = $row[$reefer_ae_1_key] ?? '';
            
            $meHsdVal = isset($row[$me_hsd]) ? floatval($row[$me_hsd]) : 0;
            $manTimeVal = isset($row[$maneuvering_time_index]) ? floatval($row[$maneuvering_time_index]) : 0;
            $ae_pararel_val = isset($row[$ae_pararel]) ? floatval($row[$ae_pararel]) : 0;
            $crane_val = isset($row[$crane_duration]) ? floatval($row[$crane_duration]) : 0;


            if ($hasNegative || ($meHsdVal != 0 && $manTimeVal == 0) || (($ae_pararel_val - $crane_val - $manTimeVal) > 3) || (($total_reefer > $reefer_ae_1_val) && ($load_ae_count == 1))) {
                if ($meHsdVal != 0 && $manTimeVal == 0) {
                    $color[$me_hsd] = 'yellow';
                    $color[$maneuvering_time_index] = 'yellow';
                }

                if (($ae_pararel_val - $crane_val - $manTimeVal) > 3) {
                    $color[$ae_pararel] = 'yellow';
                    $color[$crane_duration] = 'yellow';
                    $color[$maneuvering_time_index] = 'yellow';
                }

                if (($total_reefer > $reefer_ae_1_val) && ($load_ae_count == 1)) {
                    $loadAeKeys = [
                        $load_ae1_key,
                        $load_ae2_key,
                        $load_ae3_key,
                        $load_ae4_key,
                    ];

                    foreach ($loadAeKeys as $i => $key) {
                        $value = $loadAeValues[$i];
                        if ($value > 0 && $key !== false) {
                            $color[$key] = 'yellow';
                        }
                    }

                    $color[$reefer_20_key] = 'yellow';
                    $color[$reefer_40_key] = 'yellow';
                    $color[$reefer_ae_1_key] = 'yellow';
                }

                $filteredRows[] = [
                    'data' => $row,
                    'colors' => $color,
                    'negativeEntries' => $negativeEntries,
                ];
            }
        }

        Log::info('Sheet berhasil dibaca', [
            'sheetName' => $sheetName,
            'totalRows' => count($data),
        ]);

        $tableRows = array_map(function ($row) {
            while (!empty($row) && (end($row) === null || trim((string) end($row)) === '')) {
                array_pop($row);
            }
            return $row;
        }, $tableRows);

        $maxColumns = max(array_map('count', $tableRows));

        $headerRows = array_map(function ($row) use ($maxColumns) {
            return array_slice($row, 0, $maxColumns);
        }, $headerRows);

        $filteredRows = array_map(function ($row) {
            while (!empty($row['data']) && (end($row['data']) === null || trim((string) end($row['data'])) === '')) {
                array_pop($row['data']);
            }
            return $row;
        }, $filteredRows);

        $uniqueVessels = [];
        $deduplicatedRows = [];

        foreach ($filteredRows as $row) {
            $vesselName = $row['data'][$vesselKey] ?? null;
            $normalizedVessel = strtoupper(trim($vesselName));

            if ($vesselName && !isset($uniqueVessels[$normalizedVessel])) {
                $uniqueVessels[$normalizedVessel] = true;
                $deduplicatedRows[] = $row;
            }
        }

        session(['filteredRows' => $deduplicatedRows, 'headerRows' => $headerRows, 'sheetName' => $sheetName]);

        return view('po.upload', [
            'tanggal' => $tanggal,
            'headerRows' => $headerRows,
            'tableRows' => $deduplicatedRows,
            'sheetName' => $sheetName
        ]);
        
    }

    public function sendEmail(Request $request)
    {
        $selected = $request->input('selected_rows', []);
        $filteredRows = session('filteredRows', []);
        $headerRows = session('headerRows', []);
        $sheetName = session('sheetName', 'Unknown');

        $emailFilePath = storage_path('app\email.xlsx');
        $spreadsheetEmail = IOFactory::load($emailFilePath);
        $sheetEmail = $spreadsheetEmail->getActiveSheet();
        $emailData = $sheetEmail->toArray(null, true, true, true);

        $vesselEmails = [];
        foreach (array_slice($emailData, 1) as $row) {
            $vessel = trim($row['A']); 
            if ($vessel) {
                $key = strtoupper($vessel);
                $vesselEmails[$key] = [
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

        $roles = ['Email Kapal', 'Email SS/SI', 'Email MT', 'Email MN', 'Email DGM', 'Email GM', 'Email DPA', 'Email SSB01'];

        $normalizeHeader = function ($header) {
            $header = strtoupper(trim($header));
            $header = preg_replace('/\s+/', ' ', $header);
            $header = preg_replace('/\(\s+/', '(', $header);
            $header = preg_replace('/\s+\)/', ')', $header);
            return $header;
        };

        $vesselKey = array_search('VESSEL', array_map($normalizeHeader, $headerRows[0]));
        
        $steam_distance_key       = array_search('STEAM DISTANCE (MILES)', array_map($normalizeHeader, $headerRows[0]));
        $steam_time_key           = array_search('STEAM TIME (HOUR)', array_map($normalizeHeader, $headerRows[0]));
        $ship_speed_key           = array_search('SHIP SPEED (KNOTS)', array_map($normalizeHeader, $headerRows[0]));
        $prop_slip_key            = array_search('PROP SLIP', array_map($normalizeHeader, $headerRows[0]));
        $me_rpm_key               = array_search('ME RPM', array_map($normalizeHeader, $headerRows[0]));
        $me_mfo_key               = array_search('ME MFO', array_map($normalizeHeader, $headerRows[0]));
        $me_hsd_key               = array_search('ME HSD', array_map($normalizeHeader, $headerRows[0]));
        $ae_hsd_key               = array_search('A/E HSD', array_map($normalizeHeader, $headerRows[0]));
        $boiler_hsd_key           = array_search('BOILER HSD', array_map($normalizeHeader, $headerRows[0]));
        $boiler_mfo_key           = array_search('BOILER MFO', array_map($normalizeHeader, $headerRows[0]));
        $genset_hsd_key           = array_search('GENSET CONSUMPTION - HSD', array_map($normalizeHeader, $headerRows[0]));
        $emg_key                  = array_search('EMG', array_map($normalizeHeader, $headerRows[0]));
        $load_ae1_key             = array_search('LOAD AE 1 (KW)', array_map($normalizeHeader, $headerRows[0]));
        $load_ae2_key             = array_search('LOAD AE 2 (KW)', array_map($normalizeHeader, $headerRows[0]));
        $load_ae3_key             = array_search('LOAD AE 3 (KW)', array_map($normalizeHeader, $headerRows[0]));
        $load_ae4_key             = array_search('LOAD AE 4 (KW)', array_map($normalizeHeader, $headerRows[0]));
        $reefer_20_key            = array_search('REEFER 20"', array_map($normalizeHeader, $headerRows[0]));
        $crane_duration_key       = array_search('CRANE DURATION', array_map($normalizeHeader, $headerRows[0]));
        $total_crane_key          = array_search('TOTAL CRANE', array_map($normalizeHeader, $headerRows[0]));
        $ae_pararel_key           = array_search('AE PARAREL DURATION', array_map($normalizeHeader, $headerRows[0]));
        $reefer_40_key            = array_search('REEFER 40"', array_map($normalizeHeader, $headerRows[0]));

        $target_manuvering_Headers = ['MANEUVERING TIME (HOUR : MINUTE)', 'MANUVERING TIME'];
        $normalizedHeaders = array_map($normalizeHeader, $headerRows[0]);

        $maneuvering_time_key = null;
        foreach ($target_manuvering_Headers as $target) {
            $index = array_search(strtoupper($target), $normalizedHeaders);
            if ($index !== false) {
                $maneuvering_time_key = $index;
                break;
            }
        }

        $target_ae_mfo_Headers = ['AE MFO', 'A/E MFO'];

        $ae_mfo_key = null;
        foreach ($target_ae_mfo_Headers as $target) {
            $index = array_search(strtoupper($target), $normalizedHeaders);
            if ($index !== false) {
                $ae_mfo_key = $index;
                break;
            }
        }

        $headerRows[0] = array_map($normalizeHeader, $headerRows[0]);
        $headerRows[2] = array_map($normalizeHeader, $headerRows[2]);

        $dateKey = array_search('DATE', array_map($normalizeHeader, $headerRows[0]));
        $selisih_key = array_search('SELISIH', array_map($normalizeHeader, $headerRows[2]));
        $excess_ae_key = array_search('EXCESS AE', array_map($normalizeHeader, $headerRows[2]));
        $total_rf_key = array_search('TOTAL RF', array_map($normalizeHeader, $headerRows[2]));
        $jumlah_crane_key = array_search('JUMLAH CRANE', array_map($normalizeHeader, $headerRows[2]));
        $ae_Key = array_search('AE', array_map($normalizeHeader, $headerRows[2]));
        
        $normalizedHeaders = array_map($normalizeHeader, $headerRows[2]);
            $targetHeaders = ['AE 1 RF', 'REEFER AE 1'];

            $reefer_ae_1_key = null;

            foreach ($normalizedHeaders as $index => $header) {
                if (in_array($header, array_map($normalizeHeader, $targetHeaders))) {
                    $reefer_ae_1_key = $index;
                    break;
                }
            }  

        if ($sheetName === 'At PORT') {
            $posKey = array_search('POS', array_map($normalizeHeader, $headerRows[0]));
            $depKey = null;
            $desKey = null;
            $bl_me_key = array_search('BL ME HSD (L/H)', array_map($normalizeHeader, $headerRows[2]));
            $bl_l_nm_key = null;
            $bl_ae_key = array_search('BL AE (L/DAY)', array_map($normalizeHeader, $headerRows[2]));
            $me_maneuv_key = array_search('ME MANEUV CONSM. (L/H)', array_map($normalizeHeader, $headerRows[2]));
            $l_nm_Key = null;
        }
        elseif ($sheetName === 'At SEA') {
            $depKey = array_search('DEPARTURE', array_map($normalizeHeader, $headerRows[0]));
            $desKey = array_search('DESTINATION', array_map($normalizeHeader, $headerRows[0]));
            $posKey = null;
            $bl_me_key = array_search('BL ME', array_map($normalizeHeader, $headerRows[2]));
            $bl_l_nm_key = array_search('BL L/NM', array_map($normalizeHeader, $headerRows[2]));
            $bl_ae_key = array_search('BL AE (L/DAY)', array_map($normalizeHeader, $headerRows[2]));
            $me_maneuv_key = array_search('ME MANEUVERING CONSM. (L/H)', array_map($normalizeHeader, $headerRows[2]));
            $l_nm_Key = array_search('L/NM', array_map($normalizeHeader, $headerRows[2]));
        }

        if (empty($selected)) {
            return back()->withErrors(['Pilih minimal 1 baris untuk dikirim email.']);
        }

        foreach ($selected as $rowIndex) {
            if (!isset($filteredRows[$rowIndex])) continue;

            $row = $filteredRows[$rowIndex]['data'];
            $negativeEntries = $filteredRows[$rowIndex]['negativeEntries'] ?? [];

            $vesselName = $row[$vesselKey];
            $vesselKeyUpper = strtoupper(trim($vesselName));
            $emailsPerVessel = $vesselEmails[$vesselKeyUpper] ?? [];

            Log::info('Target email check', [
                'vessel_in_report' => $vesselName,
                'vesselKeyUpper'   => $vesselKeyUpper,
                'found_email'      => $emailsPerVessel
            ]);

            $date_val              = $row[$dateKey] ?? '';

            $selisih_val           = ceil(($row[$selisih_key] ?? 0) * 100) / 100;

            $excess_ae_val         = $row[$excess_ae_key] ?? '';
            $total_rf_val          = $row[$total_rf_key] ?? '';
            $jumlah_crane_val      = $row[$jumlah_crane_key] ?? '';
            $ae_val                = $row[$ae_Key] ?? '';

            $bl_me_val             = ceil(($row[$bl_me_key] ?? 0) * 100) / 100;

            $bl_l_nm_val           = $row[$bl_l_nm_key] ?? '';
            $bl_ae_val             = $row[$bl_ae_key] ?? '';
            $me_maneuv_val         = $row[$me_maneuv_key] ?? '';

            $l_nm_val              = ceil(($row[$l_nm_Key] ?? 0) * 100) / 100;

            $dep_val               = $row[$depKey] ?? '';
            $des_val               = $row[$desKey] ?? '';
            $pos_val               = $row[$posKey] ?? '';

            $steam_distance_val    = ceil(($row[$steam_distance_key] ?? 0) * 100) / 100;

            $steam_time_val        = $row[$steam_time_key] ?? '';
            $ship_speed_val        = $row[$ship_speed_key] ?? '';
            $prop_slip_val         = $row[$prop_slip_key] ?? '';
            $me_rpm_val            = $row[$me_rpm_key] ?? '';
            $me_mfo_val            = $row[$me_mfo_key] ?? '';
            $me_hsd_val            = $row[$me_hsd_key] ?? '';
            $ae_mfo_val            = $row[$ae_mfo_key] ?? '';
            $ae_hsd_val            = $row[$ae_hsd_key] ?? '';
            $manuvering_time_val   = $row[$maneuvering_time_key] ?? '';
            $boiler_hsd_val        = $row[$boiler_hsd_key] ?? '';
            $boiler_mfo_val        = $row[$boiler_mfo_key] ?? '';
            $genset_hsd_val        = $row[$genset_hsd_key] ?? '';
            $emg_val               = $row[$emg_key] ?? '';
            $load_ae1_val          = $row[$load_ae1_key] ?? '';
            $load_ae2_val          = $row[$load_ae2_key] ?? '';
            $load_ae3_val          = $row[$load_ae3_key] ?? '';
            $load_ae4_val          = $row[$load_ae4_key] ?? '';
            $reefer_20_val         = $row[$reefer_20_key] ?? '';
            $crane_duration_val    = $row[$crane_duration_key] ?? '';
            $total_crane_val       = $row[$total_crane_key] ?? '';
            $ae_pararel_val        = $row[$ae_pararel_key] ?? '';
            $reefer_40_val         = $row[$reefer_40_key] ?? '';

            $reefer_ae_1_val       = $row[$reefer_ae_1_key] ?? '';

            $total_ae = $ae_mfo_val + $ae_hsd_val + $genset_hsd_val;
            $total_reefer = $reefer_20_val + $reefer_40_val;

            $loadAeValues = [
                (float) ($row[$load_ae1_key] ?? 0),
                (float) ($row[$load_ae2_key] ?? 0),
                (float) ($row[$load_ae3_key] ?? 0),
                (float) ($row[$load_ae4_key] ?? 0),
            ];

            $load_ae_count = count(array_filter($loadAeValues, fn($val) => $val > 0));

            foreach ($roles as $role) {
                $targetEmail = $emailsPerVessel[$role] ?? 'marulihtgl12@gmail.com';
            
                $htmlBody = "Dear Capt/KKM <br><br>";
                $htmlBody .= "Terlampir di noon report<br>";
                $htmlBody .= "<b>{$date_val} {$sheetName}:</b><br>";

                if ($sheetName === 'At PORT') {
                    $htmlBody .= "<br>Posisi: $pos_val<br>";
                }

                if ($sheetName === 'At SEA') {
                    $htmlBody .= "<br>Posisi perjalanan dari {$dep_val} ke {$des_val}<br><br>";
                }

                if ($me_hsd_val != 0 && $manuvering_time_val == 0) {
                    $htmlBody .= "terdapat pemakaian <b>ME HSD sebanyak {$me_hsd_val} liter tanpa adanya manuvering</b><br><br>";
                }

                if (($ae_pararel_val - $crane_duration_val - $manuvering_time_val) > 3) {
                    $htmlBody .= "terdapat durasi pemakaian <b>AE Pararel berlebih selama {$ae_pararel_val} jam</b> yang disertai <b>pemakaian Crane selama {$crane_duration_val} jam</b> dan <b>durasi manuvering selama {$manuvering_time_val} jam</b>.<br><br>";
                }

                if ($total_reefer > $reefer_ae_1_val) {
                    if ($load_ae_count == 1){
                        $htmlBody .= "terdapat pemakaian <b>Reefer 20\" sebanyak {$reefer_20_val}</b> dan <b>Reefer 40\" sebanyak {$reefer_40_val}</b>, dari data tersebut maka diketahui penggunaan kedua reefer tersebut cukup berlebih dari <b>baseline yang sebesar {$reefer_ae_1_val}</b> dan hanya menggunakan <b>1 Load AE saja</b>.<br><br>";
                    }
                }


                if (!empty($negativeEntries)) {
                    foreach ($negativeEntries as $entry) {
                        [$columnName, $value] = explode(': ', $entry);

                        switch (trim($columnName)) {
                            // case 'EXCESS ME FO (%)':
                            //     $htmlBody .= "terdapat <b>excess ME FO sebesar {$value}%</b>.<br><br>";
                            //     break;
                            case 'SELISIH':
                                $htmlBody .= "terdapat pemakaian <b>ME HSD sebanyak {$me_hsd_val} liter</b> dengan <b>maneuvering time selama {$manuvering_time_val} jam</b>, dari data tersebut maka diketahui <b>konsumsi ME/Maneuvering adalah {$me_maneuv_val} Liter/Jam</b>, konsumsi tersebut cukup berlebih dari <b>baseline yang sebesar {$bl_me_val} Liter/Jam</b>.<br><br>";
                                break;
                            case 'EXCESS ME MFO L/NM (%)':
                                $htmlBody .= "terdapat pemakaian <b>ME MFO sebanyak {$me_mfo_val} liter</b> dengan total distance yang ditempuh <b>(steam distance) sebanyak {$steam_distance_val} Nm</b>, dari data tersebut maka diketahui <b>konsumsi Liter/Nm adalah {$l_nm_val} Liter/Nm</b>, konsumsi tersebut cukup berlebih dari <b>baseline yang sebesar {$bl_l_nm_val} Liter/Nm</b>.<br><br>";
                                break;
                            case 'EXCESS AE':
                                $htmlBody .= "terdapat pemakaian <b>AE MFO sebanyak {$ae_mfo_val} liter, AE HSD sebanyak {$ae_hsd_val} liter, dan konsumsi genset HSD sebanyak {$genset_hsd_val} liter</b>, dari data tersebut maka diketahui <b>konsumsi total AE per hari adalah {$total_ae} Liter/Hari</b>, konsumsi tersebut cukup berlebih dari <b>baseline yang sebesar {$bl_ae_val} Liter/Hari</b>.<br><br>";
                                break;
                        }
                    }
                }

                $htmlBody .= "Mohon dijelaskan terkait detail laporan diatas.<br><br>";
                $htmlBody .= "Atas perhatian dan kerjasama Saudara, saya mengucapkan terima kasih.<br><br>";

                $htmlBody .= "Rgrds<br>";
                $htmlBody .= "Tim Bunker<br>";

                Mail::html($htmlBody, function ($message) use ($sheetName, $vesselName, $targetEmail) {
                    $message->to($targetEmail)
                            ->subject("Permohonan penjelasan laporan {$vesselName} {$sheetName}");
                });
            }
        }

        return redirect('/send-email')
            ->with('success_email', 'All e-mails sent successfully.');
    }

    public function show()
    {
        session()->reflash();
        return view('po.upload');
    }
}