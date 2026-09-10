<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BunkerAnalysisService
{
    private const ENDPOINT = 'http://nanika.spil.co.id:3021/get-bunker-analysis';

    private const REPORT_SESSIONS = [
        14 => 'port',
        16 => 'sea',
    ];

    private const CONSUMPTION_COLUMNS = [
        'me_mfo',
        'me_hsd',
        'ae_mfo',
        'ae_hsd',
        'boiler_hsd',
        'boiler_mfo',
        'genset_consum_hsd',
        'steam_time',
    ];

    private const TEXT_COLUMNS = [
        'deck_daily_work',
        'engine_daily_work',
        'remarks',
    ];

    /**
     * Fetches both daily reports and saves one snapshot per vessel and session.
     *
     * @return array<string, int>
     */
    public function syncForDate(Carbon $reportDate): array
    {
        $syncedAt = Carbon::now();
        $result = [];

        foreach (self::REPORT_SESSIONS as $reportId => $sessionType) {
            $reports = $this->fetchReport($reportDate, $reportId);
            $snapshots = $this->aggregateSnapshots($reports, $reportDate, $sessionType, $syncedAt);

            if ($snapshots !== []) {
                DB::table('vessel_consumption_daily')->upsert(
                    $snapshots,
                    ['report_date', 'vessel_id', 'session_type'],
                    array_merge(
                        self::CONSUMPTION_COLUMNS,
                        self::TEXT_COLUMNS,
                        ['synced_at', 'updated_at']
                    )
                );
            }

            $result[$sessionType] = count($snapshots);

            Log::info('Bunker consumption snapshot synchronized.', [
                'report_date' => $reportDate->toDateString(),
                'report_id' => $reportId,
                'session_type' => $sessionType,
                'vessel_count' => count($snapshots),
            ]);
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchReport(Carbon $reportDate, int $reportId): array
    {
        $payload = [
            'tanggal' => $reportDate->format('d/m/Y'),
            'report_id' => (string) $reportId,
        ];

        $response = Http::timeout(120)
            ->acceptJson()
            ->withBody(json_encode($payload, JSON_THROW_ON_ERROR), 'application/json')
            ->get(self::ENDPOINT);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'Gagal ambil data API report_id %d (status: %d).',
                $reportId,
                $response->status()
            ));
        }

        $data = $response->json('data', []);

        if (! is_array($data)) {
            throw new RuntimeException(sprintf('Format data API report_id %d tidak valid.', $reportId));
        }

        return $data;
    }

    /**
     * API can send more than one record for the same vessel/session/date.
     * Combine them before upserting so each daily snapshot remains complete.
     *
     * @param  array<int, array<string, mixed>>  $reports
     * @return array<int, array<string, mixed>>
     */
    private function aggregateSnapshots(
        array $reports,
        Carbon $reportDate,
        string $sessionType,
        Carbon $syncedAt
    ): array {
        $aggregated = [];

        foreach ($reports as $report) {
            $vesselId = strtoupper(trim((string) ($report['vesselid'] ?? '')));

            if ($vesselId === '') {
                Log::warning('Bunker report skipped because vessel ID is empty.', [
                    'report_date' => $reportDate->toDateString(),
                    'session_type' => $sessionType,
                ]);

                continue;
            }

            if (! isset($aggregated[$vesselId])) {
                $aggregated[$vesselId] = array_merge(
                    array_fill_keys(self::CONSUMPTION_COLUMNS, 0.0),
                    array_fill_keys(self::TEXT_COLUMNS, null)
                );
            }

            foreach (self::CONSUMPTION_COLUMNS as $column) {
                $aggregated[$vesselId][$column] += $this->toFloat($report[$column] ?? 0);
            }

            foreach (self::TEXT_COLUMNS as $column) {
                $value = trim((string) ($report[$column] ?? ''));

                if ($aggregated[$vesselId][$column] === null && $value !== '') {
                    $aggregated[$vesselId][$column] = $value;
                }
            }
        }

        $now = $syncedAt->toDateTimeString();

        return array_map(
            fn (array $consumption, string $vesselId): array => array_merge($consumption, [
                'report_date' => $reportDate->toDateString(),
                'vessel_id' => $vesselId,
                'session_type' => $sessionType,
                'synced_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]),
            $aggregated,
            array_keys($aggregated)
        );
    }

    private function toFloat(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        return (float) str_replace(',', '', trim((string) $value));
    }
}
