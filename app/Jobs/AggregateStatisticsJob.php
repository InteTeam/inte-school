<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\School;
use App\Services\StatisticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled low-queue job: pre-warms statistics caches for all active schools.
 * Designed to run daily via the scheduler so dashboards load instantly.
 */
class AggregateStatisticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('low');
    }

    public function handle(StatisticsService $statistics): void
    {
        $schools = School::query()->where('is_active', true)->get();

        foreach ($schools as $school) {
            try {
                // Flush first so cache is refreshed, not served stale
                $statistics->flushCache($school);

                foreach (['week', 'month', 'term'] as $period) {
                    $statistics->getDashboard($school, $period);
                }
            } catch (\Throwable $e) {
                Log::warning('AggregateStatisticsJob: failed for school', [
                    'school_id' => $school->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AggregateStatisticsJob permanently failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
