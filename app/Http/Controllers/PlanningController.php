<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;


class PlanningController extends Controller
{
    private const PLANNING_API_BASE_URL = 'http://nanika.spil.co.id:3021';

    private function fetchPlanningApiData(string $path, array $payload, string $label, int $ttlSeconds = 300): array
    {
        $cacheKey = 'planning_api:'.sha1($path.'|'.json_encode($payload));

        return Cache::remember($cacheKey, $ttlSeconds, function () use ($path, $payload, $label) {
            $startedAt = microtime(true);

            Log::info("Mulai ambil data API {$label}", [
                'path' => $path,
                'payload' => $payload,
            ]);

            try {
                $response = Http::connectTimeout(10)
                    ->timeout(60)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->withBody(json_encode($payload), 'application/json')
                    ->get(self::PLANNING_API_BASE_URL . $path);
            } catch (\Throwable $exception) {
                Log::error("Gagal ambil data API {$label}", [
                    'path' => $path,
                    'message' => $exception->getMessage(),
                ]);

                throw new \RuntimeException("Gagal ambil data API {$label}: ".$exception->getMessage());
            }

            if (!$response->successful()) {
                throw new \RuntimeException("Gagal ambil data API {$label} (status: ".$response->status().')');
            }

            $data = $response->json();
            $rows = $data['data'] ?? [];

            Log::info("Selesai ambil data API {$label}", [
                'path' => $path,
                'rows' => is_array($rows) ? count($rows) : 0,
                'duration_seconds' => round(microtime(true) - $startedAt, 2),
            ]);

            return $rows;
        });
    }

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

    private function toNullableFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function roundUpToMultiple(float $value, int $multiple): float
    {
        return ceil($value / $multiple) * $multiple;
    }

    private function formatFuelAmount(float $value): string
    {
        return number_format($value, 2, '.', ',');
    }

    private function applyTankerBalances(
        array $reports,
        float $initialMfo,
        float $initialHsd,
        array $fuelBaselineMap = [],
        float $hargaMfo = 0,
        float $hargaHsd = 0,
        float $saldo = 0
    ): array
    {
        $mfoBalance   = $initialMfo;
        $hsdBalance   = $initialHsd;
        $saldoBalance = $saldo;

        foreach ($reports as &$report) {
            $vesselKey = strtoupper(trim($report['Vessel ID'] ?? ''));
            $baseline  = $fuelBaselineMap[$vesselKey] ?? null;

            if ($baseline === null || $baseline['speed'] <= 0) {
                $report['Isi BBM MFO'] = null;
                $report['Isi BBM HSD'] = null;
                $report['Keterangan']  = 'Fuel Baseline not found';
            } 
            else {
                $robMfoRaw = (float)($report['ROB MFO Arrival'] ?? $report['ROB MFO Sebelumnya'] ?? 0);
                $robHsdRaw = (float)($report['ROB HSD Arrival'] ?? $report['ROB HSD Sebelumnya'] ?? 0);
                $robMfo = floor(max(0, $robMfoRaw) / 1000) * 1000;
                $robHsd = floor(max(0, $robHsdRaw) / 1000) * 1000;

                $jarakNext = floor((float)($report['Jarak Next Voyage'] ?? 0));

                $day = ($baseline['speed'] > 0) ? ($jarakNext / $baseline['speed']) / 24 : 0;

                $konsStaticMe  = ceil(($baseline['static_bl_me']  * $day) / 1000) * 1000;
                $konsStaticAe  = ceil(($baseline['static_bl_ae']  * $day) / 1000) * 1000;
                $konsDynamicMe = ceil(($baseline['dynamic_bl_me'] * $day) / 1000) * 1000;

                $ss_me = $baseline['ss_me'];
                $ss_ae = $baseline['ss_ae'];

                $robNewMe = $robMfo - $konsStaticMe;
                $robNewAe = $robHsd - $konsStaticAe;

                $report['BL ME Static']  = $baseline['static_bl_me'];
                $report['BL AE Static']  = $baseline['static_bl_ae'];
                $report['BL ME Dynamic'] = $baseline['dynamic_bl_me'];
                $report['BL AE Dynamic'] = 0;

                $report['Kebutuhan MFO Static']  = $konsStaticMe;
                $report['Kebutuhan HSD Static']  = $konsStaticAe;
                $report['Kebutuhan MFO Dynamic'] = $konsDynamicMe;
                $report['Kebutuhan HSD Dynamic'] = 0;
                $keterangan = [];

                $beliMfo = 0;
                $biayaMfo = 0;

                $beliHsd = 0;
                $biayaHsd = 0;

                $totalBiaya = 0;

                $tankerMfoBefore = $mfoBalance;
                $tankerHsdBefore = $hsdBalance;

                $saldoSebelum = $saldoBalance;

                $robNewMeDynamic = $robMfo - $konsDynamicMe;

                $isiBbmMfoStatic  = $robNewMe >= $ss_me ? 0 : ceil(($ss_me - $robNewMe) / 1000) * 1000;
                $isiBbmMfoDynamic = $robNewMeDynamic >= $ss_me ? 0 : ceil(($ss_me - $robNewMeDynamic) / 1000) * 1000;

                $isiBbmMfo = $isiBbmMfoStatic + $isiBbmMfoDynamic;
                if ($mfoBalance < $isiBbmMfo) {
                    $beliMfo  = $isiBbmMfo - $mfoBalance;
                    $biayaMfo = $beliMfo * $hargaMfo;
                }

                if ($robNewAe >= $ss_ae) {
                    $isiBbmHsdStatic = 0;
                } else {
                    $isiBbmHsdStatic = ceil(($ss_ae - $robNewAe) / 1000) * 1000;
                }
                $isiBbmHsdDynamic = 0;

                $isiBbmHsd = $isiBbmHsdStatic + $isiBbmHsdDynamic;
                if ($isiBbmHsd > 0 && $hsdBalance < $isiBbmHsd) {
                    $beliHsd  = $isiBbmHsd - $hsdBalance;
                    $biayaHsd = $beliHsd * $hargaHsd;
                }

                $report['Isi BBM MFO Static']  = $isiBbmMfoStatic;
                $report['Isi BBM MFO Dynamic'] = $isiBbmMfoDynamic;
                $report['Isi BBM HSD Static']  = $isiBbmHsdStatic;
                $report['Isi BBM HSD Dynamic'] = $isiBbmHsdDynamic;

                $totalBiaya = $biayaMfo + $biayaHsd;

                if ($isiBbmMfo == 0 && $isiBbmHsd == 0) {
                    $report['Isi BBM MFO'] = 0;
                    $report['Isi BBM HSD'] = 0;
                    $keterangan[] = 'Tidak perlu isi';
                }
                elseif ($totalBiaya > 0 && $saldoBalance < $totalBiaya) {
                    $report['Isi BBM MFO'] = $isiBbmMfo;
                    $report['Isi BBM HSD'] = $isiBbmHsd;
                    $keterangan[] = 'Saldo tidak mencukupi';
                    $report['Keterangan'] = implode(' | ', $keterangan);
                    $report['_detail'] = [
                        'tanker_mfo_before'  => $tankerMfoBefore,
                        'tanker_hsd_before'  => $tankerHsdBefore,
                        'beli_pertamina_mfo' => $beliMfo,
                        'beli_pertamina_hsd' => $beliHsd,
                        'biaya_mfo'          => $biayaMfo,
                        'biaya_hsd'          => $biayaHsd,
                        'tanker_mfo_after'   => $mfoBalance,
                        'tanker_hsd_after'   => $hsdBalance,
                        'sisa_saldo'         => $saldoBalance,
                        'saldo_sebelum'      => $saldoSebelum,
                        'isi_bbm_mfo'        => $isiBbmMfo,
                        'isi_bbm_hsd'        => $isiBbmHsd,
                        'saldo_tidak_cukup'  => true,
                        'isi_bbm_mfo_static'  => $isiBbmMfoStatic,
                        'isi_bbm_mfo_dynamic' => $isiBbmMfoDynamic,
                        'isi_bbm_hsd_static'  => $isiBbmHsdStatic,
                        'isi_bbm_hsd_dynamic' => $isiBbmHsdDynamic,
                    ];
                    continue;
                }
                else {
                    if ($beliMfo > 0) {
                        if ($saldoBalance < $biayaMfo) {
                            $report['Isi BBM MFO'] = $isiBbmMfo;
                            $report['Isi BBM HSD'] = $isiBbmHsd;
                            $keterangan[] = 'Saldo tidak mencukupi.';
                            $report['Keterangan'] = implode(' | ', $keterangan);
                            $report['_detail'] = [
                                'tanker_mfo_before'  => $tankerMfoBefore,
                                'tanker_hsd_before'  => $tankerHsdBefore,
                                'beli_pertamina_mfo' => 0,
                                'beli_pertamina_hsd' => 0,
                                'biaya_mfo'          => 0,
                                'biaya_hsd'          => 0,
                                'tanker_mfo_after'   => $mfoBalance,
                                'tanker_hsd_after'   => $hsdBalance,
                                'sisa_saldo'         => $saldoBalance,
                                'saldo_sebelum'      => $saldoSebelum,
                                'isi_bbm_mfo'        => $isiBbmMfo,
                                'isi_bbm_hsd'        => $isiBbmHsd,
                                'saldo_tidak_cukup'  => true,
                                'isi_bbm_mfo_static'  => $isiBbmMfoStatic,
                                'isi_bbm_mfo_dynamic' => $isiBbmMfoDynamic,
                                'isi_bbm_hsd_static'  => $isiBbmHsdStatic,
                                'isi_bbm_hsd_dynamic' => $isiBbmHsdDynamic,
                            ];
                            continue;
                        }
                        $mfoBalance += $beliMfo;
                        $saldoBalance -= $biayaMfo;
                        $keterangan[] = 'MFO: Beli Pertamina ' . $this->formatFuelAmount($beliMfo) . ' L, isi dari Tanker ' . $this->formatFuelAmount($isiBbmMfo) . ' KL';
                    }
                    elseif ($isiBbmMfo > 0) {
                        $keterangan[] = 'MFO: Isi dari Tanker ' . $this->formatFuelAmount($isiBbmMfo) . ' L';
                    }
                    else {
                        $keterangan[] = 'MFO: Tidak perlu isi';
                    }

                    if ($beliHsd > 0) {
                        if ($saldoBalance < $biayaHsd) {
                            $report['Isi BBM MFO'] = null;
                            $report['Isi BBM HSD'] = null;
                            $keterangan[] = 'Saldo tidak mencukupi.';
                            $report['Keterangan'] = implode(' | ', $keterangan);
                            $report['_detail'] = [
                                'tanker_mfo_before'  => $tankerMfoBefore,
                                'tanker_hsd_before'  => $tankerHsdBefore,
                                'beli_pertamina_mfo' => $beliMfo,
                                'beli_pertamina_hsd' => 0,
                                'biaya_mfo'          => $biayaMfo,
                                'biaya_hsd'          => 0,
                                'tanker_mfo_after'   => $mfoBalance,
                                'tanker_hsd_after'   => $hsdBalance,
                                'sisa_saldo'         => $saldoBalance,
                                'saldo_sebelum'      => $saldoSebelum,
                                'isi_bbm_mfo'        => $isiBbmMfo,
                                'isi_bbm_hsd'        => $isiBbmHsd,
                                'saldo_tidak_cukup'  => true,
                                'isi_bbm_mfo_static'  => $isiBbmMfoStatic,
                                'isi_bbm_mfo_dynamic' => $isiBbmMfoDynamic,
                                'isi_bbm_hsd_static'  => $isiBbmHsdStatic,
                                'isi_bbm_hsd_dynamic' => $isiBbmHsdDynamic,
                            ];
                            continue;
                        }
                        $hsdBalance += $beliHsd;
                        $saldoBalance -= $biayaHsd;
                        $keterangan[] = 'HSD: Beli Pertamina ' . $this->formatFuelAmount($beliHsd) . ' L, isi dari Tanker ' . $this->formatFuelAmount($isiBbmHsd) . ' KL';
                    }
                    elseif ($isiBbmHsd > 0) {
                        $keterangan[] = 'HSD: Isi dari Tanker ' . $this->formatFuelAmount($isiBbmHsd) . ' L';
                    }
                    else {
                        $keterangan[] = 'HSD: Tidak perlu isi';
                    }

                    $mfoBalance -= $isiBbmMfo;
                    $hsdBalance -= $isiBbmHsd;

                    $report['Isi BBM MFO'] = $isiBbmMfo;
                    $report['Isi BBM HSD'] = $isiBbmHsd;
                }

                $report['Keterangan'] = implode(' | ', $keterangan);

                $report['_detail'] = [
                    'tanker_mfo_before'  => $tankerMfoBefore,
                    'tanker_hsd_before'  => $tankerHsdBefore,
                    'beli_pertamina_mfo' => $beliMfo,
                    'beli_pertamina_hsd' => $beliHsd,
                    'biaya_mfo'          => $biayaMfo,
                    'biaya_hsd'          => $biayaHsd,
                    'tanker_mfo_after'   => $mfoBalance,
                    'tanker_hsd_after'   => $hsdBalance,
                    'sisa_saldo'         => $saldoBalance,
                    'saldo_sebelum'      => $saldoSebelum,
                    'isi_bbm_mfo'        => $isiBbmMfo,
                    'isi_bbm_hsd'        => $isiBbmHsd,
                    'saldo_tidak_cukup'  => false,
                    'isi_bbm_mfo_static'  => $isiBbmMfoStatic,
                    'isi_bbm_mfo_dynamic' => $isiBbmMfoDynamic,
                    'isi_bbm_hsd_static'  => $isiBbmHsdStatic,
                    'isi_bbm_hsd_dynamic' => $isiBbmHsdDynamic,
                ];
            }
        }

        unset($report);

        return $reports;
    }

    private function loadRefuelingPlanMap(): array
    {
        try {
            $rows = DB::table('refueling_plan')
                ->select('kapal', 'mfo_day_at_sea', 'ae_day_at_sea', 'speed')
                ->get();
        } catch (\Throwable $exception) {
            Log::error('Gagal ambil data refueling_plan', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $vessel = strtoupper(trim($row->kapal ?? ''));
            if ($vessel === '') {
                continue;
            }

            $map[$vessel] = [
                'mfo_day_at_sea' => $this->toNullableFloat($row->mfo_day_at_sea ?? null),
                'ae_day_at_sea' => $this->toNullableFloat($row->ae_day_at_sea ?? null),
                'speed' => $this->toNullableFloat($row->speed ?? null),
            ];
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
        $dayDiff = $etb ? $noon->diffInDays($etb, false) : null;

        \Log::info('splitRouteByPosition called', [
            'currentRouteWithNext' => $currentRouteWithNext,
            'position'             => $position,
            'etbDate'              => $etbDate,
            'noonReportDate'       => $noonReportDate,
            'etb'                  => $etb ? $etb->toDateString() : null,
            'noon'                 => $noon->toDateString(),
            'dayDiff'              => $dayDiff,
        ]);

        $matchIndexes = [];

        for ($i = 0; $i <= count($routeArray) - $positionLength; $i++) {
            $slice = array_slice($routeArray, $i, $positionLength);
            if ($slice === $positionArray) {
                $matchIndexes[] = $i;
            }
        }

        \Log::info('Match indexes found', ['matchIndexes' => $matchIndexes]);

        if (empty($matchIndexes)) {
            \Log::info('No match found, returning original route');
            return [$currentRouteWithNext, ''];
        }

        if ($dayDiff !== null) {
            if ($dayDiff <= 2) {
                // ambil match terakhir
                $matchIndex = end($matchIndexes);
            } elseif ($dayDiff == 3 || $dayDiff == 4) {
                // ambil match kedua terakhir (jika ada)
                $count = count($matchIndexes);
                $matchIndex = $count >= 2 ? $matchIndexes[$count - 2] : end($matchIndexes);
            } else {
                // default: ambil match pertama
                $matchIndex = $matchIndexes[0];
            }
        } else {
            $matchIndex = $matchIndexes[0];
        }

        \Log::info('Match decision', [
            'chosenIndex'  => $matchIndex,
        ]);

        $firstPart = array_slice($routeArray, 0, $matchIndex + 1);
        $secondPart = array_slice($routeArray, $matchIndex + 1);

        $result = [implode('.', $firstPart), implode('.', $secondPart)];

        \Log::info('Final split result', ['result' => $result]);

        return $result;
    }


    function reorderReport($grouped, $noon_report_groupedByVessel = [], $l_nm_map = [], $refueling_plan_map = [], $port_id_map = [], $jarak_map = [], string $dvs_formattedDate = '', string $noon_report_formattedDate = '') {
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

        $refuelingPlan = $refueling_plan_map[$vesselKey] ?? [];
        $mfoDayAtSea = $refuelingPlan['mfo_day_at_sea'] ?? null;
        $aeDayAtSea = $refuelingPlan['ae_day_at_sea'] ?? null;
        $speed = $refuelingPlan['speed'] ?? null;

        //////////////////////////////////////////////////////////////////////////

        $lnm_hsd = $l_nm_map[$vesselKey]['hsd'] ?? null;
        $lnm_mfo = $l_nm_map[$vesselKey]['mfo'] ?? null;

        //////////////////////////////////////////////////////////////////////////

        $rob_hsd_sebelumnya = null;
        $rob_mfo_sebelumnya = null;
        $rob_hsd_berthing = null;
        $rob_mfo_berthing = null;
        $kebutuhan_hsd_next_route = null;
        $kebutuhan_mfo_next_route = null;
        $pengisian_hsd = null;
        $pengisian_mfo = null;
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

            //// at port ////

            $pos_port = strtoupper(trim($robRow['pos'] ?? ''));
            $parts = preg_split('/[\s,.\-]+/', $pos_port, -1, PREG_SPLIT_NO_EMPTY);

            $last_two_parts = array_slice($parts, -2);
            $last_two_combined = trim(implode(' ', $last_two_parts));

            $last_pos = !empty($parts) ? end($parts) : null;

            $last_pos_id = $port_id_map[$last_two_combined] ?? null;

            if ($last_pos_id !== null) {
                $last_pos = $last_two_combined;
            }

            if ($last_pos_id === null && $last_pos !== null) {
                $last_pos_id = $port_id_map[$last_pos] ?? null;
            }

            \Log::info("Vessel: $vesselKey, Pos: $pos_port, Last Pos: $last_pos");

            if ($departurePort || $destinationPort) {
                $position = $departurePort . '.' . $destinationPort;
            } 

            if ($pos_port !== '') {
                $position = $last_pos_id ?? '';
            }
        }

        $total_current_route = count(explode('.', $currentRouteWithNext));

        $etbArray = explode(' ', $grouped['etb']);

        // sea
        if ($dtg !== null) {
            $etb = $etbArray[0] ?? null;
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
        if ($dtg === null && $rob_hsd_sebelumnya !== null) {
            $dtg = 'AT PORT';
            $departureName = $pos_port;
            $departurePort = $last_pos;

            $etb = $etbArray[0] ?? null;
            $noonReport = $noon_report_formattedDate;          

            list($part1, $part2) = $this->splitRouteByPosition($currentRouteWithNext, $position, $etb, $noonReport);
        
            $total_part2_route = count(explode('.', $part2));

            if ($part2 !== ''){
                $part1_array = explode('.', $part1);
                $last_of_part1 = end($part1_array);
                $part2 = $last_of_part1 . '.' . $part2;

                if ($total_part2_route == 1) {
                    $part2_distance = $calculateDistance($part2);
                    $distanceCurrent = $part2_distance;
                }

                if ($total_part2_route > 1) {
                    $part2_distance = $calculateDistance($part2);
                    $distanceCurrent = $part2_distance;
                }
            } 
            else {
                if (strpos($currentRouteWithNext, $position) === false) {
                    $distanceCurrent = null;
                }
                else {
                    $distanceCurrent = 0;
                }
            }

            if ($position == '') {
                $distanceCurrent = null;
            }  
        }

        //////////////////////////////////////////////////////////////////////////

        if ($rob_hsd_sebelumnya !== null && $distanceCurrent !== null && $lnm_hsd !== null) {
            $rob_hsd_berthing = $rob_hsd_sebelumnya - ($distanceCurrent * $lnm_hsd);
        }

        if ($rob_mfo_sebelumnya !== null && $distanceCurrent !== null && $lnm_mfo !== null) {
            $rob_mfo_berthing = $rob_mfo_sebelumnya - ($distanceCurrent * $lnm_mfo);
        }

        if ($vesselKey === 'BSA') {
            \Log::info('BSA debug reorderReport', [
                'rob_mfo_sebelumnya' => $rob_mfo_sebelumnya,
                'rob_hsd_sebelumnya' => $rob_hsd_sebelumnya,
                'distanceCurrent'    => $distanceCurrent,
                'lnm_mfo'            => $lnm_mfo,
                'lnm_hsd'            => $lnm_hsd,
            ]);
        }

        if ($distanceNext !== null && $speed !== null && $speed > 0) {
            if ($aeDayAtSea !== null) {
                $kebutuhan_hsd_next_route = $this->roundUpToMultiple((($distanceNext / $speed) / 24) * $aeDayAtSea, 1000);
            }

            if ($mfoDayAtSea !== null) {
                $kebutuhan_mfo_next_route = $this->roundUpToMultiple((($distanceNext / $speed) / 24) * $mfoDayAtSea, 1000);
            }
        }

        if ($kebutuhan_hsd_next_route !== null && $rob_hsd_berthing !== null) {
            $selisih_hsd = $kebutuhan_hsd_next_route - $rob_hsd_berthing;
            if ($selisih_hsd >= 0) {
                $pengisian_hsd = ceil(($selisih_hsd * 1.1) / 5000) * 5000;
            } else {
                $pengisian_hsd = 0;
            }
        }

        if ($kebutuhan_mfo_next_route !== null && $rob_mfo_berthing !== null) {
            $selisih_mfo = $kebutuhan_mfo_next_route - $rob_mfo_berthing;
            if ($selisih_mfo >= 0) {
                $pengisian_mfo = ceil(($selisih_mfo * 1.1) / 5000) * 5000;
            } else {
                $pengisian_mfo = 0;
            }
        }
        
        /////////////////////////////////////////////////////////////////////////

        $ordered = [
            'Vessel ID' => $grouped['vesselid'] ?? null,
            'Voyage' => $grouped['voyage'] ?? null,

            'New Current Voyage (FROM)' => $currentRouteWithNext,

            'ETA' => $grouped['eta'] ?? null,
            'ETB' => $grouped['etb'] ?? null,
            'ETD' => $grouped['etd'] ?? null,

            'Next Voyage (Sailing Route)' => $grouped['sailing_route'] ?? null,

            'Distance to Go' => $dtg,

            'Departure Name' => $departureName,
            'Destination Name' => $destinationName,

            'Departure Port' => $departurePort,
            'Destination Port' => $destinationPort,

            'Position' => $position,

            'Rute yang Sudah dilewati' => $part1,
            'Sisa Rute' => $part2,

            'Jarak Sisa Rute' => $part2_distance,

            'Total Jarak Sisa Voyage' => $distanceCurrent,
            // 'Total jarak voyage' => $calculateDistance($currentRouteWithNext),

            'Jarak Next Voyage' => $distanceNext,

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
            'New Current Voyage (FROM)',
            'ETA',
            'ETB',
            'ETD',
            'Next Voyage (Sailing Route)',
            'Distance to Go',
            'Departure Name',
            'Destination Name',
            'Departure Port',
            'Destination Port',
            'Position',	
            'Rute yang Sudah dilewati',
            'Sisa Rute',
            'Jarak Sisa Rute',	
            'Total Jarak Sisa Voyage',
            'Jarak Next Voyage',
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
            'Isi BBM MFO',
            'Isi BBM HSD',
            'Keterangan',
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
            unset($reportRow['_detail']);
            $reportRow['Koreksi'] = '';
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
        set_time_limit(1200);
        $reportDate = $request->input('report_date', date('Y-m-d'));
        $next_week_date = $request->input('next_week_date', date('Y-m-d', strtotime('+7 days')));
        
        $dvs_formattedDate = \Carbon\Carbon::parse($reportDate)->format('d/m/Y');
        $dvs_formattedNextWeekDate = \Carbon\Carbon::parse($next_week_date)->format('d/m/Y');
        $noon_report_formattedDate = \Carbon\Carbon::now()->subDay()->format('d/m/Y');
        $hasFetchedPlanning = $request->boolean('hit_api');

        $planningViewData = [
            'reportDate' => $dvs_formattedDate,
            'nextWeekDate' => $dvs_formattedNextWeekDate,
            'headerRows' => [],
            'report' => [],
            'noon_report_formattedDate' => $noon_report_formattedDate,
            'hasFetchedPlanning' => $hasFetchedPlanning,
        ];

        if (!$hasFetchedPlanning) {
            return view('po.planning', $planningViewData);
        }

        if (!$request->filled('rob_tanker_mfo') || !$request->filled('rob_tanker_hsd')
            || !$request->filled('harga_mfo') || !$request->filled('harga_hsd')
            || !$request->filled('input_saldo_rp')) {
            return view('po.planning', array_merge($planningViewData, [
                'error' => 'ROB Tanker, Harga BBM, dan Input Saldo wajib diisi.',
            ]));
        }

        $robTankerMfo = $this->toNullableFloat($request->input('rob_tanker_mfo')) * 1000;
        $robTankerHsd = $this->toNullableFloat($request->input('rob_tanker_hsd')) * 1000;
        $hargaMfo     = $this->toNullableFloat($request->input('harga_mfo'));
        $hargaHsd     = $this->toNullableFloat($request->input('harga_hsd'));
        $saldo        = $this->toNullableFloat($request->input('input_saldo_rp'));

        if ($hargaMfo === null || $hargaHsd === null || $saldo === null) {
            return view('po.planning', array_merge($planningViewData, [
                'error' => 'Harga BBM dan Saldo harus berupa angka.',
            ]));
        }

        if ($robTankerMfo === null || $robTankerHsd === null) {
            return view('po.planning', array_merge($planningViewData, [
                'error' => 'ROB Tanker MFO dan HSD harus berupa angka.',
            ]));
        }

        $dvs_basePayload = [
            "tanggal_awal" => $dvs_formattedDate,
            "tanggal_akhir" => $dvs_formattedNextWeekDate,
        ];

        // True  -> Using Mock Data
        // False -> Using API
        if (false) {
            $dvs_reports_raw = $this->getMockDvsReports();
        } else {
            try {
                $dvs_reports_raw = $this->fetchPlanningApiData('/get-data-dvs', $dvs_basePayload, 'DVS', 300);
            } catch (\RuntimeException $exception) {
                return view('po.planning', array_merge($planningViewData, [
                    'error' => $exception->getMessage(),
                ]));
            }
        }

         // filter data valid
        $dvs_reports = array_values(array_filter($dvs_reports_raw, function ($row) {
            return isset($row['vesselid']) && trim($row['vesselid']) !== '' && trim($row['etb']) !== '';
        }));

        // langsung sort berdasarkan etb
        usort($dvs_reports, function ($a, $b) {
            $etbA = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $a['etb']);
            $etbB = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $b['etb']);
            return $etbA->lt($etbB) ? -1 : 1;
        });

        \Log::info("\n");
        \Log::info(str_repeat('-', 50) . PHP_EOL);
        \Log::info("\n");

        ////////////////////////////////////////////////////////////////////////

        $noon_report_basePayload = [
            "tanggal" => $noon_report_formattedDate,
        ];

        
        // True  -> Using Mock Data
        // False -> Using API
        if (false) {
            $noon_report_groupedByVessel = $this->getMockNoonReportMap();
        } else { 
            $reportIds = [14, 16];
            $noon_report_allReports = [];

            foreach ($reportIds as $reportId) {
                $rob_payload = $noon_report_basePayload;
                $rob_payload['report_id'] = (string)$reportId;

                try {
                    $rob_reports = $this->fetchPlanningApiData('/get-bunker-analysis', $rob_payload, 'bunker analysis report_id '.$reportId, 1800);
                } catch (\RuntimeException $exception) {
                    return view('po.planning', array_merge($planningViewData, [
                        'error' => $exception->getMessage(),
                        'report14' => [],
                        'report16' => [],
                    ]));
                }

                $noon_report_allReports = array_merge($noon_report_allReports, $rob_reports);
            } 

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
                        'pos' => isset($r['pos']) ? strtoupper($r['pos']) : null,
                    ])
                    ->first();
            })->toArray();
        }

        ///////////////////////////////////////////////////////////////////////////

        $l_nm_map = $this->loadLnmMap();
        $refueling_plan_map = $this->loadRefuelingPlanMap();

        ///////////////////////////////////////////////////////////////////////////

        $master_route_payload = [
            "load_port" => "-",
            "disc_port" => "-",
        ];

        // True  -> Using Mock Data
        // False -> Using API
        if (false) {
            $port_id_map = [
                'BENGKULU'=>'IDBKS',  'JAKARTA'=>'IDJKT',   'BALIKPAPAN'=>'IDBPN',
                'SAMARINDA'=>'IDSRI', 'SURABAYA'=>'IDSUB',  'SAMPIT'=>'IDSPT',
                'BATULICIN'=>'IDBTW', 'TERNATE'=>'IDTTE',   'TIMIKA'=>'IDTIM',
                'KENDARI'=>'IDKDI',   'TARAKAN'=>'IDTRK',   'MAKASSAR'=>'IDMAK',
                'BANJARMASIN'=>'IDBDJ','PONTIANAK'=>'IDPNK','BELAWAN'=>'IDBLW',
                'BAUBAU'=>'IDBUW',    'BAU BAU'=>'IDBUW',   'TABONEO'=>'IDTAB',
                'MANOKWARI'=>'IDMKW', 'MERAUKE'=>'IDMKQ',   'JAYAPURA'=>'IDDJJ',
                'AMBON'=>'IDAMQ',     'TUAL'=>'IDTUA',      'GORONTALO'=>'IDGTO',
                'PANTOLOAN'=>'IDPAL', 'PALU'=>'IDPAL',      'KUALA TANJUNG'=>'IDKTJ',
                'PADANG'=>'IDPDG',    'BATAM'=>'IDBTM',     'PERAWANG'=>'IDPER',
                'KETAPANG'=>'IDKTG',
            ];
            $jarak_map = $this->getMockJarakMap();
        } else {
            try {
                $master_route_data = $this->fetchPlanningApiData('/get-master-route', $master_route_payload, 'master route', 86400);
            } catch (\RuntimeException $exception) {
                return view('po.planning', array_merge($planningViewData, [
                    'error' => $exception->getMessage(),
                ]));
            }

            $port_id_map = [];

            foreach ($master_route_data as $port) {
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
            $port_id_map['SBY'] = 'IDSUB';
            $port_id_map['BAU BAU'] = 'IDBUW';
            $port_id_map['KUALATANJUNG'] = 'IDKTJ';
            $port_id_map['KUALA TANJUNG'] = 'IDKTJ';

            //////////////////////////////////////////////////////////////////////

            $jarak_map = [];

            foreach ($master_route_data as $route) {
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
        }

        /////////////////////////////////////////////////////////////////////

        // Pass ke reorderReport
        $all_dvs_Reports = array_map(
            fn($grouped) => $this->reorderReport($grouped, $noon_report_groupedByVessel, $l_nm_map, $refueling_plan_map, $port_id_map, $jarak_map, $dvs_formattedDate, $noon_report_formattedDate),
            $dvs_reports
        );

        $fuelBaselineMap = $this->loadFuelBaselineMap();
        $all_dvs_Reports = $this->applyTankerBalances($all_dvs_Reports, $robTankerMfo, $robTankerHsd, $fuelBaselineMap, $hargaMfo, $hargaHsd, $saldo);

        //////////////////////////////////////////////////////////////////////////

        $headerRows = $all_dvs_Reports ? array_keys(reset($all_dvs_Reports)) : [];

        session([
            'planning_reports' => $all_dvs_Reports,
            'planning_date' => $dvs_formattedDate,
            'planning_next_week_date' => $dvs_formattedNextWeekDate,
            'noon_report_formattedDate' => $noon_report_formattedDate,
        ]);

        Log::info('Refueling planning rendered', [
            'report_date' => $dvs_formattedDate,
            'next_week_date' => $dvs_formattedNextWeekDate,
            'rows' => count($all_dvs_Reports),
            'columns' => count($headerRows),
        ]);

        return view('po.planning', [
            'reportDate'   => $dvs_formattedDate,
            'nextWeekDate' => $dvs_formattedNextWeekDate,
            'headerRows' => $headerRows,
            'report' => $all_dvs_Reports,
            'noon_report_formattedDate' => $noon_report_formattedDate,
            'hasFetchedPlanning' => $hasFetchedPlanning,
            'hargaMfo' => $hargaMfo,
            'hargaHsd' => $hargaHsd,
            'saldo' => $saldo,
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

    private function loadFuelBaselineMap(): array
    {
        try {
            $rows = DB::table('fuel_baselines')
                ->select('vessel_id', 'static_bl_me', 'static_bl_ae', 'dynamic_bl_me', 'speed', 'ss_multiplier_me', 'ss_multiplier_ae')
                ->get();

            $map = [];
            foreach ($rows as $row) {
                $map[strtoupper(trim($row->vessel_id))] = [
                    'static_bl_me'  => (float) $row->static_bl_me * 24,
                    'static_bl_ae'  => (float) $row->static_bl_ae * 24,
                    'dynamic_bl_me' => (float) $row->dynamic_bl_me * 24,
                    'speed'         => (float) $row->speed,
                    'ss_me' => (float) $row->static_bl_me * 24 * (float) $row->ss_multiplier_me,
                    'ss_ae' => (float) $row->static_bl_ae * 24 * (float) $row->ss_multiplier_ae,
                ];
            }
            return $map;
        }
        catch (\Throwable $e) {
            Log::error('Failed to load fuel_baselines', ['message' => $e->getMessage()]);
            return [];
        }
    }
}
