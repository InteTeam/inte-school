<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\School;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates school-level statistics for the dashboard and stats API.
 * All results are cached in Redis: `school:{id}:stats:{type}:{period}` TTL 1h.
 */
final class StatisticsService
{
    public const PERMISSIONS = ['attendance', 'messages', 'homework', 'users'];

    /**
     * Returns combined dashboard data — used by Admin statistics page.
     *
     * @return array<string, mixed>
     */
    public function getDashboard(School $school, string $period = 'week'): array
    {
        return Cache::remember(
            "school:{$school->id}:stats:dashboard:{$period}",
            3600,
            function () use ($school, $period): array {
                [$from, $to] = $this->periodRange($period);

                return [
                    'attendance' => $this->getAttendanceStats($school, $from, $to),
                    'messages' => $this->getMessageStats($school, $from, $to),
                    'homework' => $this->getHomeworkStats($school, $from, $to),
                    'users' => $this->getUserStats($school),
                    'period' => $period,
                ];
            }
        );
    }

    /**
     * Returns only the data allowed by the API key's permission set.
     *
     * @param  string[]  $permissions
     * @return array<string, mixed>
     */
    public function getForApi(School $school, array $permissions, string $period = 'week'): array
    {
        [$from, $to] = $this->periodRange($period);
        $data = [];

        if (in_array('attendance', $permissions, true)) {
            $data['attendance'] = $this->getAttendanceStats($school, $from, $to);
        }
        if (in_array('messages', $permissions, true)) {
            $data['messages'] = $this->getMessageStats($school, $from, $to);
        }
        if (in_array('homework', $permissions, true)) {
            $data['homework'] = $this->getHomeworkStats($school, $from, $to);
        }
        if (in_array('users', $permissions, true)) {
            $data['users'] = $this->getUserStats($school);
        }

        $data['period'] = $period;
        $data['school'] = $school->slug;

        return $data;
    }

    /**
     * Flush cached dashboard stats for a school (called by observers on data change).
     */
    public function flushCache(School $school): void
    {
        foreach (['week', 'month', 'term'] as $period) {
            Cache::forget("school:{$school->id}:stats:dashboard:{$period}");
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttendanceStats(School $school, Carbon $from, Carbon $to): array
    {
        $counts = DB::table('attendance_records')
            ->where('school_id', $school->id)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $present = (int) ($counts['present'] ?? 0);
        $absent = (int) ($counts['absent'] ?? 0);
        $late = (int) ($counts['late'] ?? 0);
        $total = $present + $absent + $late;

        return [
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'total' => $total,
            'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getMessageStats(School $school, Carbon $from, Carbon $to): array
    {
        $sent = DB::table('messages')
            ->where('school_id', $school->id)
            ->whereBetween('sent_at', [$from, $to])
            ->count();

        $totalRecipients = DB::table('message_recipients')
            ->where('school_id', $school->id)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $read = DB::table('message_recipients')
            ->where('school_id', $school->id)
            ->whereNotNull('read_at')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        return [
            'sent' => $sent,
            'total_recipients' => $totalRecipients,
            'read' => $read,
            'engagement_rate' => $totalRecipients > 0
                ? round(($read / $totalRecipients) * 100, 1)
                : 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getHomeworkStats(School $school, Carbon $from, Carbon $to): array
    {
        $counts = DB::table('tasks')
            ->where('school_id', $school->id)
            ->where('type', 'homework')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $done = (int) ($counts['done'] ?? 0);
        $total = $counts->sum();

        return [
            'todo' => (int) ($counts['todo'] ?? 0),
            'in_progress' => (int) ($counts['in_progress'] ?? 0),
            'done' => $done,
            'cancelled' => (int) ($counts['cancelled'] ?? 0),
            'completion_rate' => $total > 0 ? round(($done / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserStats(School $school): array
    {
        $counts = DB::table('school_user')
            ->where('school_id', $school->id)
            ->whereNotNull('accepted_at')
            ->selectRaw('role, count(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role');

        return [
            'admin' => (int) ($counts['admin'] ?? 0),
            'teacher' => (int) ($counts['teacher'] ?? 0),
            'support' => (int) ($counts['support'] ?? 0),
            'parent' => (int) ($counts['parent'] ?? 0),
            'student' => (int) ($counts['student'] ?? 0),
            'total' => (int) $counts->sum(),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periodRange(string $period): array
    {
        return match ($period) {
            'month' => [now()->subMonth(), now()],
            'term' => [now()->subMonths(3), now()],
            default => [now()->subWeek(), now()], // 'week'
        };
    }
}
