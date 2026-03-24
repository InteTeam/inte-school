<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AttendanceRecord;
use App\Services\AttendanceService;

class AttendanceObserver
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
    ) {}

    public function created(AttendanceRecord $record): void
    {
        $this->flush($record);
    }

    public function updated(AttendanceRecord $record): void
    {
        $this->flush($record);
    }

    private function flush(AttendanceRecord $record): void
    {
        $register = $record->register;

        if ($register !== null) {
            $this->attendanceService->flushStatsCache(
                $record->school_id,
                $register->register_date->toDateString(),
            );
        }
    }
}
