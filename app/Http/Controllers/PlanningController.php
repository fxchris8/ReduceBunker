<?php

namespace App\Http\Controllers;

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
    public function upload(Request $request)
    {   
        Log::info(PHP_EOL . str_repeat('=', 100) . PHP_EOL);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());

        $sheet = $spreadsheet->getSheetByName('SBY');
        $data = $sheet->toArray(null, true, true, true);
        $headerRows = array_slice($data, 3, 2);
        $tableRows  = array_slice($data, 5);

        $l_nm_FilePath = storage_path('app\L_NM.xlsx');
        $spreadsheet_l_nm = IOFactory::load($l_nm_FilePath);
        $sheet_l_nm = $spreadsheet_l_nm->getActiveSheet();
        $l_nm_Data = $sheet_l_nm->toArray(null, true, true, true);

        $l_nm_map = [];

        foreach (array_slice($l_nm_Data, 1) as $row) {
            $vessel = trim($row['B']); 
            $mfo    = trim($row['C']);
            $hsd    = trim($row['D']);
            if ($vessel && $mfo && $hsd) {
                $l_nm_map[$vessel] = ['mfo' => $mfo, 'hsd' => $hsd];
            }
        }

        $jarak_FilePath = storage_path('app\Data Jarak.xlsx');
        $spreadsheet_jarak = IOFactory::load($jarak_FilePath);
        $sheet_jarak = $spreadsheet_jarak->getActiveSheet();
        $jarak_Data = $sheet_jarak->toArray(null, true, true, true);

        $jarak_map = [];

        foreach (array_slice($jarak_Data, 1) as $row) {
            $from = trim($row['A']);
            $to   = trim($row['B']);
            $dist = floatval($row['C']);
            if ($from && $to && $dist) {
                $jarak_map[$from][$to] = $dist;
            }
        }

        if ($sheet) {
            $tanggal = $sheet->getCell('A3')->getValue();
        }

        $normalizeHeader = function ($header) {
            $header = strtoupper(trim($header));
            $header = preg_replace('/\s+/', ' ', $header);
            $header = preg_replace('/\(\s+/', '(', $header);
            $header = preg_replace('/\s+\)/', ')', $header);
            return $header;
        };

        $headerRow0 = array_map($normalizeHeader, $headerRows[0]);
        $headerRow1 = array_map($normalizeHeader, $headerRows[1]);

        $combinedHeader = [];
        foreach ($headerRow0 as $key => $value) {
            $combinedHeader[$key] = ($headerRow1[$key] ?? '') ?: $value;
        }
        $desiredHeaders = ['NO', 'VESSEL', 'VOYAGE', 'FROM', 'ETA', 'ETB', 'ETD', 'SAILING ROUTE'];

        $selectedIndexes = array_keys(array_filter($combinedHeader, function ($header) use ($desiredHeaders) {
            return in_array($header, $desiredHeaders);
        }));

        $extraColumns = [
            'Jarak FROM Route' => 'Jarak FROM Route',
            'Jarak NEW Route' => 'Jarak NEW Route',
            'L/NM MFO' => 'L/NM MFO',
            'L/NM HSD' => 'L/NM HSD',
            'ROB Tiba MFO' => 'ROB Tiba MFO',
            'ROB Tiba HSD' => 'ROB Tiba HSD',
            'Kebutuhan Next Route MFO' => 'Kebutuhan Next Route MFO',
            'Kebutuhan Next Route HSD' => 'Kebutuhan Next Route HSD',
            'Pengisian MFO' => 'Pengisian MFO',
            'Pengisian HSD' => 'Pengisian HSD',
        ];

        $filteredHeaderRows = array_map(function ($row, $rowIndex) use ($selectedIndexes, $extraColumns) {
            $filtered = array_intersect_key($row, array_flip($selectedIndexes));
            if ($rowIndex === 1) {
                $filtered = array_merge($filtered, $extraColumns);
            }
            return $filtered;
        }, $headerRows, array_keys($headerRows));

        $filteredTableRows = array_map(function ($row) use ($selectedIndexes) {
            return array_intersect_key($row, array_flip($selectedIndexes));
        }, $tableRows);

        $vesselIndex = null;
        foreach ($combinedHeader as $key => $value) {
            if (strtoupper(trim($value)) === 'VESSEL') {
                $vesselIndex = $key;
                break;
            }
        }

        $indexByHeader = [];
        foreach ($combinedHeader as $key => $value) {
            $indexByHeader[$value] = $key;
        }

        $vesselIndex = $indexByHeader['VESSEL'] ?? null;
        $fromIndex   = $indexByHeader['FROM'] ?? null;
        $routeIndex  = $indexByHeader['SAILING ROUTE'] ?? null;

        $calculateDistance = function (?string $route) use ($jarak_map) {
            $route = strtoupper(trim($route ?? ''));
            if ($route === '') return 0.0;

            // pisah dengan: " - ", "-", ",", "_", ".", atau spasi berlebih
            $parts = preg_split('/\s*-\s*|,|_|\s+|\./', $route);
            $parts = array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));

            $total = 0.0;
            for ($i = 0; $i < count($parts) - 1; $i++) {
                $o = $parts[$i];
                $d = $parts[$i + 1];

                if ($o === $d) {
                    $total += 0.0;
                    continue;
                }

                if (isset($jarak_map[$o][$d])) {
                    $total += (float) $jarak_map[$o][$d];
                } else {
                    Log::warning("Distance not found for route: $o to $d \n");
                    // kalau ingin tandai gagal, bisa return null; untuk sekarang keep 0 + break
                    return null;
                    // break;
                }
            }
            return $total;
        };

        $enhancedTableRows = array_map(function ($row) use (
            $vesselIndex, $fromIndex, $routeIndex, $l_nm_map, $calculateDistance
        ) {
            $rawName    = $vesselIndex ? ($row[$vesselIndex] ?? '') : '';
            $vesselKey  = strtoupper(preg_replace('/[^A-Z0-9]/', '', $rawName));

            $mfo = $l_nm_map[$vesselKey]['mfo'] ?? '';
            $hsd = $l_nm_map[$vesselKey]['hsd'] ?? '';

            $mfo_val = is_numeric($mfo) ? (float) $mfo : 0.0;
            $hsd_val = is_numeric($hsd) ? (float) $hsd : 0.0;

            $sailingRoute = $routeIndex ? trim($row[$routeIndex] ?? '') : '';
            
            $first_next_route = preg_split('/\s*-\s*|,|_|\s+|\./', $sailingRoute);
            $first_next_route = array_values(array_filter(array_map('trim', $first_next_route), fn($v) => $v !== ''));
            $first_next_route = $first_next_route[0];

            $fromRoute    = $fromIndex ? trim($row[$fromIndex]  ?? '') : '';
            
            if ($fromRoute !== '') {
                $fromRoute .= '.' . $first_next_route;
            }

            Log::info("\n");
            Log::info("From Route", [
                'fromRoute' => $fromRoute,
            ]);

            $from_route_dist = $calculateDistance($fromRoute);

            Log::info('Next Route', [
                'sailingRoute' => $sailingRoute,
            ]);

            $next_route_dist = $calculateDistance($sailingRoute);

            $kebutuhan_from_route_mfo = ceil(250000 - ($from_route_dist * $mfo_val));
            $kebutuhan_from_route_hsd = ceil(50000 - ($from_route_dist * $hsd_val));

            $kebutuhan_next_route_mfo = ceil($next_route_dist * $mfo_val);
            $kebutuhan_next_route_hsd = ceil($next_route_dist * $hsd_val);

            $selisih_mfo = $kebutuhan_next_route_mfo - $kebutuhan_from_route_mfo;
            $selisih_hsd = $kebutuhan_next_route_hsd - $kebutuhan_from_route_hsd;

            if ($selisih_mfo <= 0) {
                $pengisian_mfo = 0;
                $pengisian_hsd = 0;
            } else {
                $pengisian_mfo = $selisih_mfo * 1.1;
                $pengisian_hsd = $selisih_hsd * 1.1;
            }

            $row['jarak_from'] = $from_route_dist;
            $row['jarak_next'] = $next_route_dist;

            $row['LNM_MFO']    = $mfo_val;
            $row['LNM_HSD']    = $hsd_val;

            $row['kebutuhan_from_route_mfo'] = $kebutuhan_from_route_mfo;
            $row['kebutuhan_from_route_hsd'] = $kebutuhan_from_route_hsd;

            $row['kebutuhan_next_route_mfo'] = $kebutuhan_next_route_mfo;
            $row['kebutuhan_next_route_hsd'] = $kebutuhan_next_route_hsd;

            $row['pengisian_mfo'] = ceil($pengisian_mfo / 5000) * 5000;
            $row['pengisian_hsd'] = ceil($pengisian_hsd / 5000) * 5000;

            return $row;
        }, $filteredTableRows);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $rowIndex = 1;

        foreach ($filteredHeaderRows as $headerRow) {
            $colIndex = 1;
            if ($rowIndex === 2) {
                $headerRow[] = 'Koreksi';
            }
            foreach ($headerRow as $cell) {
                $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue($columnLetter . $rowIndex, $cell);

                $sheet->getStyle($columnLetter . $rowIndex)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'd0d0d0'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $colIndex++;
            }
            $rowIndex++;
        }

        foreach ($enhancedTableRows as $tableRow) {
            $colIndex = 1;
            foreach ($tableRow as $cell) {
                $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue($columnLetter . $rowIndex, $cell);

                $fillColor = ($rowIndex % 2 == 0) ? 'e0e0e0' : 'FFFFFF';
                $sheet->getStyle($columnLetter . $rowIndex)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $fillColor],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $colIndex++;
            }
            $rowIndex++;
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'planning_') . '.xlsx';
        $writer->save($tempFile);

        Mail::raw('Berikut terlampir hasil upload Refueling Planning.', function ($message) use ($tempFile) {
            $message->to('marulihtgl12@gmail.com') // oilmgt@spil.co.id
                    ->subject('Refueling Planning Excel')
                    ->attach($tempFile, [
                        'as' => 'refueling_planning.xlsx',
                        'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]);
        });

        return view('po.planning', [
            'tanggal' => $tanggal,
            'headerRows' => $filteredHeaderRows,
            'tableRows' => $enhancedTableRows,
            'extraColumns' => $extraColumns
        ]);

    }

    public function download(Request $request)
    {
        $headerRows = json_decode($request->input('headerRows'), true);
        $tableRows = json_decode($request->input('tableRows'), true);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $rowIndex = 1;

        foreach ($headerRows as $headerRow) {
            $colIndex = 1;
            
            if ($rowIndex === 2) { 
                $headerRow[] = 'Koreksi';
            }

            foreach ($headerRow as $cell) {
                $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue($columnLetter . $rowIndex, $cell);

                $sheet->getStyle($columnLetter . $rowIndex)->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'd0d0d0'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $colIndex++;
            }
            $rowIndex++;
        }


        foreach ($tableRows as $tableRow) {
            $colIndex = 1;
            foreach ($tableRow as $cell) {
                $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue($columnLetter . $rowIndex, $cell);

                $fillColor = ($rowIndex % 2 == 0) ? 'e0e0e0' : 'FFFFFF';
                $sheet->getStyle($columnLetter . $rowIndex)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $fillColor],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $colIndex++;
            }
            $rowIndex++;
        }


        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="refueling_planning.xlsx"',
        ]);
    }


    public function show()
    {
        session()->reflash();
        return view('po.planning');
    }
}