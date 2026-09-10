<?php

namespace App\Http\Controllers;

use App\Models\VesselConsumptionDaily;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SummaryController extends Controller
{
    public function show(Request $request)
    {
        $request->validate([
            "report_month" => ["nullable", 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $reportMonth = $request->input(
            "report_month",
            now("Asia/Jakarta")->format("Y-m"),
        );
        $monthStart = Carbon::createFromFormat(
            "!Y-m",
            $reportMonth,
            "Asia/Jakarta",
        )->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $consumptions = VesselConsumptionDaily::query()
            ->select("vessel_id")
            ->selectRaw("SUM(me_mfo) as me_mfo")
            ->selectRaw("SUM(me_hsd) as me_hsd")
            ->selectRaw("SUM(ae_mfo) as ae_mfo")
            ->selectRaw("SUM(ae_hsd) as ae_hsd")
            ->selectRaw("SUM(boiler_hsd) as boiler_hsd")
            ->selectRaw("SUM(boiler_mfo) as boiler_mfo")
            ->selectRaw("SUM(genset_consum_hsd) as genset_consum_hsd")
            ->selectRaw("MAX(synced_at) as last_synced_at")
            ->whereBetween("report_date", [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ])
            ->groupBy("vessel_id")
            ->orderBy("vessel_id")
            ->get();

        $totalConsumption = [
            "me_mfo" => $consumptions->sum("me_mfo"),
            "me_hsd" => $consumptions->sum("me_hsd"),
            "ae_mfo" => $consumptions->sum("ae_mfo"),
            "ae_hsd" => $consumptions->sum("ae_hsd"),
            "boiler_hsd" => $consumptions->sum("boiler_hsd"),
            "boiler_mfo" => $consumptions->sum("boiler_mfo"),
            "genset_consum_hsd" => $consumptions->sum("genset_consum_hsd"),
        ];

        $totalMfo = (float) (
            $totalConsumption["me_mfo"] +
            $totalConsumption["ae_mfo"] +
            $totalConsumption["boiler_mfo"]
        );
        $totalHsd = (float) (
            $totalConsumption["me_hsd"] +
            $totalConsumption["ae_hsd"] +
            $totalConsumption["boiler_hsd"] +
            $totalConsumption["genset_consum_hsd"]
        );

        $mfoBreakdown = [
            "boiler" => (float) $totalConsumption["boiler_mfo"],
            "me" => (float) $totalConsumption["me_mfo"],
            "ae" => (float) $totalConsumption["ae_mfo"],
        ];

        $hsdBreakdown = [
            "boiler" => (float) $totalConsumption["boiler_hsd"],
            "me" => (float) $totalConsumption["me_hsd"],
            "ae" => (float) $totalConsumption["ae_hsd"],
            "genset" => (float) $totalConsumption["genset_consum_hsd"],
        ];

        $lastSyncedAt = VesselConsumptionDaily::query()
            ->whereBetween("report_date", [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ])
            ->max("synced_at");

        return view("dashboard", [
            "reportMonth" => $reportMonth,
            "reportMonthLabel" => $monthStart
                ->locale("id")
                ->translatedFormat("F Y"),
            "consumptions" => $consumptions,
            "totalConsumption" => $totalConsumption,
            "totalMfo" => $totalMfo,
            "totalHsd" => $totalHsd,
            "mfoBreakdown" => $mfoBreakdown,
            "hsdBreakdown" => $hsdBreakdown,
            "lastSyncedAt" => $lastSyncedAt
                ? Carbon::parse($lastSyncedAt)->timezone("Asia/Jakarta")
                : null,
        ]);
    }

    public function dailyDetail(Request $request)
    {
        $request->validate([
            "vessel_id" => ["required", "string"],
            "report_month" => ["nullable", 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $vesselId = $request->input("vessel_id");
        $reportMonth = $request->input(
            "report_month",
            now("Asia/Jakarta")->format("Y-m"),
        );
        $monthStart = Carbon::createFromFormat(
            "!Y-m",
            $reportMonth,
            "Asia/Jakarta",
        )->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $records = VesselConsumptionDaily::query()
            ->where("vessel_id", $vesselId)
            ->whereBetween("report_date", [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ])
            ->orderBy("report_date", "asc")
            ->orderBy("session_type", "asc")
            ->get();

        $fuelKeys = [
            "me_mfo",
            "me_hsd",
            "ae_mfo",
            "ae_hsd",
            "boiler_hsd",
            "boiler_mfo",
            "genset_consum_hsd",
        ];

        $dailyRows = $records->map(function ($record) use ($fuelKeys) {
            $session = strtolower($record->session_type);
            $row = [
                "id" => $record->id,
                "date" => $record->report_date->format("Y-m-d"),
                "date_label" => $record->report_date
                    ->locale("id")
                    ->translatedFormat("d M Y"),
                "session_type" => $session,
                "position_label" =>
                    $session === "sea"
                        ? "At Sea"
                        : ($session === "port"
                            ? "At Port"
                            : ucfirst($session)),
                "remarks" => $record->remarks ?: "-",
                "engine_daily_work" => $record->engine_daily_work ?: "-",
                "deck_daily_work" => $record->deck_daily_work ?: "-",
                "steam_time" => $session === "sea" ? (float) ($record->steam_time ?? 0) : 0.0,
            ];
            foreach ($fuelKeys as $key) {
                $row[$key] = (float) $record->{$key};
            }
            return $row;
        });

        $totals = [];
        foreach ($fuelKeys as $key) {
            $portSum = (float) $records
                ->where("session_type", "port")
                ->sum($key);
            $seaSum = (float) $records->where("session_type", "sea")->sum($key);
            $totals[$key] = [
                "port" => $portSum,
                "sea" => $seaSum,
                "total" => $portSum + $seaSum,
            ];
        }

        $seaSteamTime = (float) $records->where("session_type", "sea")->sum("steam_time");
        $totals["steam_time"] = [
            "port" => 0.0,
            "sea" => $seaSteamTime,
            "total" => $seaSteamTime,
        ];

        $summary = [
            "total_mfo" => (float) (
                ($totals["me_mfo"]["total"] ?? 0) +
                ($totals["ae_mfo"]["total"] ?? 0) +
                ($totals["boiler_mfo"]["total"] ?? 0)
            ),
            "total_hsd" => (float) (
                ($totals["me_hsd"]["total"] ?? 0) +
                ($totals["ae_hsd"]["total"] ?? 0) +
                ($totals["boiler_hsd"]["total"] ?? 0) +
                ($totals["genset_consum_hsd"]["total"] ?? 0)
            ),
            "mfo_breakdown" => [
                "boiler" => (float) ($totals["boiler_mfo"]["total"] ?? 0),
                "me" => (float) ($totals["me_mfo"]["total"] ?? 0),
                "ae" => (float) ($totals["ae_mfo"]["total"] ?? 0),
            ],
            "hsd_breakdown" => [
                "boiler" => (float) ($totals["boiler_hsd"]["total"] ?? 0),
                "me" => (float) ($totals["me_hsd"]["total"] ?? 0),
                "ae" => (float) ($totals["ae_hsd"]["total"] ?? 0),
                "genset" => (float) ($totals["genset_consum_hsd"]["total"] ?? 0),
            ],
        ];

        $averages = $this->calculateVesselAverages($records, $totals);

        return response()->json([
            "vessel_id" => $vesselId,
            "report_month" => $reportMonth,
            "report_month_label" => $monthStart
                ->locale("id")
                ->translatedFormat("F Y"),
            "daily_rows" => $dailyRows,
            "totals" => $totals,
            "summary" => $summary,
            "averages" => $averages,
        ]);
    }

    public function updateDailyDetail(Request $request)
    {
        $validated = $request->validate([
            "vessel_id" => ["required", "string"],
            "report_date" => ["required", "date_format:Y-m-d"],
            "session_type" => ["required", "in:port,sea"],
            "report_month" => ["required", 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            "me_mfo" => ["nullable", "numeric", "min:0"],
            "me_hsd" => ["nullable", "numeric", "min:0"],
            "ae_mfo" => ["nullable", "numeric", "min:0"],
            "ae_hsd" => ["nullable", "numeric", "min:0"],
            "boiler_hsd" => ["nullable", "numeric", "min:0"],
            "boiler_mfo" => ["nullable", "numeric", "min:0"],
            "genset_consum_hsd" => ["nullable", "numeric", "min:0"],
        ]);

        $vesselId = $validated["vessel_id"];
        $reportDate = $validated["report_date"];
        $reportMonth = $validated["report_month"];
        $sessionType = $validated["session_type"] ?? null;

        $fuelKeys = [
            'me_mfo', 'me_hsd', 'ae_mfo', 'ae_hsd',
            'boiler_hsd', 'boiler_mfo', 'genset_consum_hsd',
        ];

        $updateData = [
            'synced_at' => now(),
        ];
        foreach ($fuelKeys as $key) {
            if ($request->has($key)) {
                $updateData[$key] = (float) ($validated[$key] ?? 0);
            }
        }

        VesselConsumptionDaily::updateOrCreate(
            [
                'report_date' => $reportDate,
                'vessel_id' => $vesselId,
                'session_type' => $sessionType,
            ],
            $updateData
        );

        $monthStart = Carbon::createFromFormat(
            "!Y-m",
            $reportMonth,
            "Asia/Jakarta",
        )->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $records = VesselConsumptionDaily::query()
            ->where("vessel_id", $vesselId)
            ->whereBetween("report_date", [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ])
            ->get();

        $totals = [];
        $dashboardVesselTotals = [];
        foreach ($fuelKeys as $key) {
            $portSum = (float) $records->where("session_type", "port")->sum($key);
            $seaSum = (float) $records->where("session_type", "sea")->sum($key);
            $totals[$key] = [
                "port" => $portSum,
                "sea" => $seaSum,
                "total" => $portSum + $seaSum,
            ];
            $dashboardVesselTotals[$key] = $portSum + $seaSum;
        }

        $dashboardGrandTotals = [];
        foreach ($fuelKeys as $key) {
            $dashboardGrandTotals[$key] = (float) VesselConsumptionDaily::whereBetween(
                "report_date",
                [$monthStart->toDateString(), $monthEnd->toDateString()]
            )->sum($key);
        }

        $vesselSummary = [
            "total_mfo" => (float) (
                ($totals["me_mfo"]["total"] ?? 0) +
                ($totals["ae_mfo"]["total"] ?? 0) +
                ($totals["boiler_mfo"]["total"] ?? 0)
            ),
            "total_hsd" => (float) (
                ($totals["me_hsd"]["total"] ?? 0) +
                ($totals["ae_hsd"]["total"] ?? 0) +
                ($totals["boiler_hsd"]["total"] ?? 0) +
                ($totals["genset_consum_hsd"]["total"] ?? 0)
            ),
            "mfo_breakdown" => [
                "boiler" => (float) ($totals["boiler_mfo"]["total"] ?? 0),
                "me" => (float) ($totals["me_mfo"]["total"] ?? 0),
                "ae" => (float) ($totals["ae_mfo"]["total"] ?? 0),
            ],
            "hsd_breakdown" => [
                "boiler" => (float) ($totals["boiler_hsd"]["total"] ?? 0),
                "me" => (float) ($totals["me_hsd"]["total"] ?? 0),
                "ae" => (float) ($totals["ae_hsd"]["total"] ?? 0),
                "genset" => (float) ($totals["genset_consum_hsd"]["total"] ?? 0),
            ],
        ];

        $dashboardGrandSummary = [
            "total_mfo" => (float) (
                ($dashboardGrandTotals["me_mfo"] ?? 0) +
                ($dashboardGrandTotals["ae_mfo"] ?? 0) +
                ($dashboardGrandTotals["boiler_mfo"] ?? 0)
            ),
            "total_hsd" => (float) (
                ($dashboardGrandTotals["me_hsd"] ?? 0) +
                ($dashboardGrandTotals["ae_hsd"] ?? 0) +
                ($dashboardGrandTotals["boiler_hsd"] ?? 0) +
                ($dashboardGrandTotals["genset_consum_hsd"] ?? 0)
            ),
            "mfo_breakdown" => [
                "boiler" => (float) ($dashboardGrandTotals["boiler_mfo"] ?? 0),
                "me" => (float) ($dashboardGrandTotals["me_mfo"] ?? 0),
                "ae" => (float) ($dashboardGrandTotals["ae_mfo"] ?? 0),
            ],
            "hsd_breakdown" => [
                "boiler" => (float) ($dashboardGrandTotals["boiler_hsd"] ?? 0),
                "me" => (float) ($dashboardGrandTotals["me_hsd"] ?? 0),
                "ae" => (float) ($dashboardGrandTotals["ae_hsd"] ?? 0),
                "genset" => (float) ($dashboardGrandTotals["genset_consum_hsd"] ?? 0),
            ],
        ];

        $seaSteamTime = (float) $records->where("session_type", "sea")->sum("steam_time");
        $totals["steam_time"] = [
            "port" => 0.0,
            "sea" => $seaSteamTime,
            "total" => $seaSteamTime,
        ];

        $vesselAverages = $this->calculateVesselAverages($records, $totals);

        return response()->json([
            "success" => true,
            "message" => "Nilai konsumsi berhasil diperbarui.",
            "vessel_id" => $vesselId,
            "report_date" => $reportDate,
            "session_type" => $sessionType,
            "totals" => $totals,
            "summary" => $vesselSummary,
            "averages" => $vesselAverages,
            "dashboard_vessel_totals" => $dashboardVesselTotals,
            "dashboard_grand_totals" => $dashboardGrandTotals,
            "dashboard_grand_summary" => $dashboardGrandSummary,
        ]);
    }

    public function exportDailyDetail(Request $request)
    {
        $request->validate([
            "vessel_id" => ["required", "string"],
            "report_month" => ["nullable", 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $vesselId = $request->input("vessel_id");
        $reportMonth = $request->input(
            "report_month",
            now("Asia/Jakarta")->format("Y-m"),
        );
        $monthStart = Carbon::createFromFormat(
            "!Y-m",
            $reportMonth,
            "Asia/Jakarta",
        )->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $records = VesselConsumptionDaily::query()
            ->where("vessel_id", $vesselId)
            ->whereBetween("report_date", [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ])
            ->orderBy("report_date", "asc")
            ->orderBy("session_type", "asc")
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Daily Detail");

        $headers = [
            "No",
            "Tanggal",
            "Posisi",
            "Steam Time",
            "ME MFO",
            "ME HSD",
            "AE MFO",
            "AE HSD",
            "Boiler HSD",
            "Boiler MFO",
            "Genset Consumption",
            "Remarks",
            "Engine Daily Work",
            "Deck Daily Work",
        ];
        $sheet->fromArray($headers, null, "A1");

        $sheet->getStyle("A1:N1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
            "fill" => [
                "fillType" => Fill::FILL_SOLID,
                "startColor" => ["rgb" => "1F2937"],
            ], // dark gray
            "alignment" => [
                "horizontal" => Alignment::HORIZONTAL_CENTER,
                "vertical" => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $rowIndex = 2;
        $fuelKeys = ['me_mfo', 'me_hsd', 'ae_mfo', 'ae_hsd', 'boiler_hsd', 'boiler_mfo', 'genset_consum_hsd'];

        foreach ($records as $idx => $row) {
            $session = strtolower($row->session_type);
            $posLabel = $session === 'sea' ? 'At Sea' : ($session === 'port' ? 'At Port' : ucfirst($session));

            $datarow = [
                $idx + 1,
                $row->report_date ? $row->report_date->format('d/m/Y') : '-',
                $posLabel,
                (float) ($row->steam_time ?? 0),
                (float) $row->me_mfo,
                (float) $row->me_hsd,
                (float) $row->ae_mfo,
                (float) $row->ae_hsd,
                (float) $row->boiler_hsd,
                (float) $row->boiler_mfo,
                (float) $row->genset_consum_hsd,
                $row->remarks ?: '-',
                $row->engine_daily_work ?: '-',
                $row->deck_daily_work ?: '-',
            ];
            $sheet->fromArray($datarow, null, "A{$rowIndex}");
            $rowIndex++;
        }

        $totalRow = [
            'Total Akumulasi', '', '',
            (float) $records->sum('steam_time'),
            (float) $records->sum('me_mfo'),
            (float) $records->sum('me_hsd'),
            (float) $records->sum('ae_mfo'),
            (float) $records->sum('ae_hsd'),
            (float) $records->sum('boiler_hsd'),
            (float) $records->sum('boiler_mfo'),
            (float) $records->sum('genset_consum_hsd'),
            '', '', ''
        ];
        $sheet->fromArray($totalRow, null, "A{$rowIndex}");
        $sheet->mergeCells("A{$rowIndex}:C{$rowIndex}");
        $sheet->getStyle("A{$rowIndex}:N{$rowIndex}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0F2FE']],
        ]);

        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = "Detail_Konsumsi_{$vesselId}_{$reportMonth}.xlsx";

        return response()->streamDownload(function() use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function calculateVesselAverages($records, array $totals): array
    {
        $totalEffectiveSteamTime = 0.0;
        $uniqueDates = [];

        foreach ($records as $record) {
            $dateKey = $record->report_date instanceof Carbon
                ? $record->report_date->toDateString()
                : substr((string) $record->report_date, 0, 10);
            $uniqueDates[$dateKey] = true;

            // Steam time hanya berlaku saat kapal beroperasi di laut ("At Sea")
            if (strtolower((string) $record->session_type) === "sea") {
                $st = (float) ($record->steam_time ?? 0);
                $totalEffectiveSteamTime += min(24.0, $st);
            }
        }

        $totalDays = count($uniqueDates);
        $totalMe = (float) (($totals["me_mfo"]["total"] ?? 0) + ($totals["me_hsd"]["total"] ?? 0));
        $totalAe = (float) (
            ($totals["ae_mfo"]["total"] ?? 0) +
            ($totals["ae_hsd"]["total"] ?? 0) +
            ($totals["genset_consum_hsd"]["total"] ?? 0)
        );

        $avgMePerHour = $totalEffectiveSteamTime > 0 ? round($totalMe / $totalEffectiveSteamTime, 2) : 0.0;
        $avgMePerDay = round($avgMePerHour * 24.0, 2);
        $avgAePerDay = $totalDays > 0 ? round($totalAe / $totalDays, 2) : 0.0;

        return [
            "total_steam_time" => round($totalEffectiveSteamTime, 2),
            "total_days" => $totalDays,
            "total_me" => round($totalMe, 2),
            "total_ae" => round($totalAe, 2),
            "avg_me_per_hour" => $avgMePerHour,
            "avg_me_per_day" => $avgMePerDay,
            "avg_ae_per_day" => $avgAePerDay,
        ];
    }

    public function exportMonthly(Request $request)
    {
        $request->validate([
            'report_month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $reportMonth = $request->input('report_month', now('Asia/Jakarta')->format('Y-m'));
        $monthStart = Carbon::createFromFormat('!Y-m', $reportMonth, 'Asia/Jakarta')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $monthLabel = $monthStart->locale('id')->translatedFormat('F Y');

        $consumptions = VesselConsumptionDaily::query()
            ->select('vessel_id')
            ->selectRaw('SUM(me_mfo) as me_mfo')
            ->selectRaw('SUM(me_hsd) as me_hsd')
            ->selectRaw('SUM(ae_mfo) as ae_mfo')
            ->selectRaw('SUM(ae_hsd) as ae_hsd')
            ->selectRaw('SUM(boiler_hsd) as boiler_hsd')
            ->selectRaw('SUM(boiler_mfo) as boiler_mfo')
            ->selectRaw('SUM(genset_consum_hsd) as genset_consum_hsd')
            ->whereBetween('report_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->groupBy('vessel_id')
            ->orderBy('vessel_id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Konsumsi Bulanan');

        // Judul Laporan
        $sheet->setCellValue('A1', 'LAPORAN KONSUMSI BUNKER - ' . strtoupper($monthLabel));
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Header Kolom
        $headers = [
            'No', 'Vessel ID', 'ME MFO', 'ME HSD', 
            'AE MFO', 'AE HSD', 'Boiler HSD', 'Boiler MFO', 
            'Genset Consumption'
        ];
        $sheet->fromArray($headers, null, 'A3');
        $sheet->getStyle('A3:I3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Baris Data
        $rowIndex = 4;
        foreach ($consumptions as $idx => $c) {
            $row = [
                $idx + 1,
                $c->vessel_id,
                (float) $c->me_mfo,
                (float) $c->me_hsd,
                (float) $c->ae_mfo,
                (float) $c->ae_hsd,
                (float) $c->boiler_hsd,
                (float) $c->boiler_mfo,
                (float) $c->genset_consum_hsd,
            ];
            $sheet->fromArray($row, null, "A{$rowIndex}");
            $rowIndex++;
        }

        // Baris Total Akumulasi
        $totalRow = [
            'Total', '',
            (float) $consumptions->sum('me_mfo'),
            (float) $consumptions->sum('me_hsd'),
            (float) $consumptions->sum('ae_mfo'),
            (float) $consumptions->sum('ae_hsd'),
            (float) $consumptions->sum('boiler_hsd'),
            (float) $consumptions->sum('boiler_mfo'),
            (float) $consumptions->sum('genset_consum_hsd'),
        ];
        $sheet->fromArray($totalRow, null, "A{$rowIndex}");
        $sheet->mergeCells("A{$rowIndex}:B{$rowIndex}");
        $sheet->getStyle("A{$rowIndex}:I{$rowIndex}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0F2FE']],
        ]);

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = "Konsumsi_Bulanan_{$reportMonth}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
