<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendAttendanceAlertJob;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRegister;
use App\Models\School;
use App\Models\Scopes\SchoolScope;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class AttendanceService
{
    /**
     * Open an existing register for the given class/date or create a new one.
     */
    public function openOrGetRegister(
        School $school,
        User $teacher,
        string $classId,
        Carbon $date,
        ?string $period = null,
    ): AttendanceRegister {
        // firstOrCreate respects $fillable and would reject school_id; use explicit pattern.
        $existing = AttendanceRegister::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $school->id)
            ->where('class_id', $classId)
            ->whereDate('register_date', $date->toDateString())
            ->where('period', $period)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        /** @var AttendanceRegister $register */
        $register = AttendanceRegister::forceCreate([
            'school_id' => $school->id,
            'class_id' => $classId,
            'teacher_id' => $teacher->id,
            'register_date' => $date->toDateString(),
            'period' => $period,
        ]);

        return $register;
    }

    /**
     * Mark a student's attendance. Upserts per (register_id, student_id).
     * Dispatches a parent alert job if absent and not pre-notified.
     */
    public function mark(
        AttendanceRegister $register,
        string $studentId,
        string $status,
        User $markedBy,
        string $markedVia = 'manual',
        ?string $notes = null,
        bool $preNotified = false,
    ): AttendanceRecord {
        // updateOrCreate respects $fillable and would reject school_id; use explicit pattern.
        $existing = AttendanceRecord::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $register->school_id)
            ->where('register_id', $register->id)
            ->where('student_id', $studentId)
            ->first();

        if ($existing !== null) {
            $existing->forceFill([
                'status' => $status,
                'marked_by' => $markedBy->id,
                'marked_via' => $markedVia,
                'pre_notified' => $preNotified,
                'notes' => $notes,
            ])->save();
            $record = $existing;
        } else {
            /** @var AttendanceRecord $record */
            $record = AttendanceRecord::forceCreate([
                'school_id' => $register->school_id,
                'register_id' => $register->id,
                'student_id' => $studentId,
                'status' => $status,
                'marked_by' => $markedBy->id,
                'marked_via' => $markedVia,
                'pre_notified' => $preNotified,
                'notes' => $notes,
            ]);
        }

        if ($status === 'absent' && ! $preNotified) {
            $this->dispatchAbsenceAlerts($register->school_id, $markedBy->id, $studentId);
        }

        return $record;
    }

    /**
     * Aggregate present/absent/late counts for a school on a given date.
     * Cached for 1 hour; invalidated by AttendanceObserver on each mark.
     *
     * @return array{present: int, absent: int, late: int, date: string}
     */
    public function getDailyStats(School $school, Carbon $date): array
    {
        $cacheKey = "school:{$school->id}:attendance:{$date->toDateString()}";

        /** @var array{present: int, absent: int, late: int, date: string} $stats */
        $stats = Cache::remember($cacheKey, 3600, function () use ($school, $date): array {
            $rows = DB::table('attendance_records')
                ->join('attendance_registers', 'attendance_records.register_id', '=', 'attendance_registers.id')
                ->where('attendance_registers.school_id', $school->id)
                ->whereDate('attendance_registers.register_date', $date->toDateString())
                ->select('attendance_records.status', DB::raw('count(*) as cnt'))
                ->groupBy('attendance_records.status')
                ->pluck('cnt', 'status')
                ->toArray();

            return [
                'present' => (int) ($rows['present'] ?? 0),
                'absent' => (int) ($rows['absent'] ?? 0),
                'late' => (int) ($rows['late'] ?? 0),
                'date' => $date->toDateString(),
            ];
        });

        return $stats;
    }

    /**
     * Flush the daily stats cache for a register's date.
     */
    public function flushStatsCache(string $schoolId, string $date): void
    {
        Cache::forget("school:{$schoolId}:attendance:{$date}");
    }

    private function dispatchAbsenceAlerts(string $schoolId, string $senderId, string $studentId): void
    {
        $student = User::find($studentId);

        if ($student === null) {
            return;
        }

        $guardianIds = DB::table('guardian_student')
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->pluck('guardian_id')
            ->all();

        foreach ($guardianIds as $guardianId) {
            SendAttendanceAlertJob::dispatch(
                $schoolId,
                $senderId,
                $guardianId,
                $student->name,
            );
        }
    }
}
