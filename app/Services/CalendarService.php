<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Calendar;
use App\Models\CalendarEvent;
use App\Models\School;
use App\Models\Scopes\SchoolScope;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Handles calendar CRUD and month-window caching.
 *
 * NOTE: Event lists are ordered by `starts_at ASC` — documented exception to
 * the global `orderBy created_at DESC` rule, justified by chronological UX.
 */
final class CalendarService
{
    /** @param array<string, mixed> $data */
    public function createCalendar(School $school, array $data): Calendar
    {
        /** @var Calendar $calendar */
        $calendar = Calendar::forceCreate([
            'school_id' => $school->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'department_label' => $data['department_label'] ?? null,
            'color' => $data['color'] ?? null,
            'is_public' => (bool) ($data['is_public'] ?? false),
        ]);

        return $calendar;
    }

    /** @param array<string, mixed> $data */
    public function updateCalendar(Calendar $calendar, array $data): Calendar
    {
        $calendar->update([
            'name' => $data['name'] ?? $calendar->name,
            'type' => $data['type'] ?? $calendar->type,
            'department_label' => $data['department_label'] ?? $calendar->department_label,
            'color' => $data['color'] ?? $calendar->color,
            'is_public' => isset($data['is_public']) ? (bool) $data['is_public'] : $calendar->is_public,
        ]);

        return $calendar;
    }

    public function deleteCalendar(Calendar $calendar): void
    {
        $calendar->delete();
    }

    /** @param array<string, mixed> $data */
    public function createEvent(Calendar $calendar, User $creator, array $data): CalendarEvent
    {
        /** @var CalendarEvent $event */
        $event = CalendarEvent::forceCreate([
            'school_id' => $calendar->school_id,
            'calendar_id' => $calendar->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'all_day' => (bool) ($data['all_day'] ?? false),
            'location' => $data['location'] ?? null,
            'meta' => $data['meta'] ?? null,
            'created_by' => $creator->id,
        ]);

        return $event;
    }

    /** @param array<string, mixed> $data */
    public function updateEvent(CalendarEvent $event, array $data): CalendarEvent
    {
        $event->forceFill([
            'title' => $data['title'] ?? $event->title,
            'description' => $data['description'] ?? $event->description,
            'starts_at' => $data['starts_at'] ?? $event->starts_at,
            'ends_at' => $data['ends_at'] ?? $event->ends_at,
            'all_day' => isset($data['all_day']) ? (bool) $data['all_day'] : $event->all_day,
            'location' => $data['location'] ?? $event->location,
            'meta' => $data['meta'] ?? $event->meta,
        ])->save();

        return $event;
    }

    public function deleteEvent(CalendarEvent $event): void
    {
        $event->delete();
    }

    /**
     * Load all events for a school's calendars in a month window.
     * Ordered by starts_at ASC — documented exception to default DESC.
     * Cached per school + calendar + month.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMonthEvents(School $school, int $year, int $month): array
    {
        $cacheKey = "school:{$school->id}:calendar:all:{$year}-{$month}";

        /** @var array<int, array<string, mixed>> $events */
        $events = Cache::remember($cacheKey, 3600, function () use ($school, $year, $month): array {
            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            return CalendarEvent::withoutGlobalScope(SchoolScope::class)
                ->join('calendars', 'calendar_events.calendar_id', '=', 'calendars.id')
                ->where('calendar_events.school_id', $school->id)
                ->whereNull('calendars.deleted_at')
                ->whereBetween('calendar_events.starts_at', [$start, $end])
                ->orderBy('calendar_events.starts_at', 'asc')
                ->select('calendar_events.*', 'calendars.color as calendar_color', 'calendars.name as calendar_name')
                ->get()
                ->map(fn (CalendarEvent $e): array => $this->serializeEvent($e))
                ->values()
                ->all();
        });

        return $events;
    }

    /**
     * Load events from public calendars only (for parents/students).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPublicMonthEvents(School $school, int $year, int $month): array
    {
        $cacheKey = "school:{$school->id}:calendar:public:{$year}-{$month}";

        /** @var array<int, array<string, mixed>> $events */
        $events = Cache::remember($cacheKey, 3600, function () use ($school, $year, $month): array {
            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            return CalendarEvent::withoutGlobalScope(SchoolScope::class)
                ->join('calendars', 'calendar_events.calendar_id', '=', 'calendars.id')
                ->where('calendar_events.school_id', $school->id)
                ->where('calendars.is_public', true)
                ->whereNull('calendars.deleted_at')
                ->whereBetween('calendar_events.starts_at', [$start, $end])
                ->orderBy('calendar_events.starts_at', 'asc')
                ->select('calendar_events.*', 'calendars.color as calendar_color', 'calendars.name as calendar_name')
                ->get()
                ->map(fn (CalendarEvent $e): array => $this->serializeEvent($e))
                ->values()
                ->all();
        });

        return $events;
    }

    /**
     * Flush all cached month windows for a calendar's school.
     */
    public function flushCalendarCache(string $schoolId, string $calendarId, Carbon $month): void
    {
        $y = $month->year;
        $m = $month->month;

        Cache::forget("school:{$schoolId}:calendar:all:{$y}-{$m}");
        Cache::forget("school:{$schoolId}:calendar:public:{$y}-{$m}");
        Cache::forget("school:{$schoolId}:calendar:{$calendarId}:{$y}-{$m}");
    }

    /** @return array<string, mixed> */
    private function serializeEvent(CalendarEvent $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'start' => (string) $event->starts_at,
            'end' => (string) $event->ends_at,
            'allDay' => $event->all_day,
            'location' => $event->location,
            'backgroundColor' => $event->getAttribute('calendar_color'),
            'calendarName' => $event->getAttribute('calendar_name'),
        ];
    }
}
