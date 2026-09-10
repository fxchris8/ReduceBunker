<?php

namespace App\Console\Commands;

use App\Services\BunkerAnalysisService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class SyncDailyBunkerConsumption extends Command
{
    protected $signature = 'bunker:sync-daily {--date= : Tanggal report dalam format YYYY-MM-DD}';

    protected $description = 'Sinkronkan konsumsi bunker harian dari API';

    public function handle(BunkerAnalysisService $bunkerAnalysis): int
    {
        $date = $this->option('date');

        try {
            $reportDate = $date
                ? Carbon::createFromFormat('!Y-m-d', $date, 'Asia/Jakarta')
                : Carbon::now('Asia/Jakarta')->startOfDay();
        } catch (Throwable) {
            $this->error('Format --date harus YYYY-MM-DD.');

            return self::INVALID;
        }

        try {
            $result = $bunkerAnalysis->syncForDate($reportDate);
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Sinkronisasi %s selesai: %d vessel Port, %d vessel Sea.',
            $reportDate->format('Y-m-d'),
            $result['port'] ?? 0,
            $result['sea'] ?? 0,
        ));

        return self::SUCCESS;
    }
}
