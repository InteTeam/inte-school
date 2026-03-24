<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\CalendarEvent;
use App\Services\CalendarService;
use Carbon\Carbon;

class CalendarEventObserver
{
    public function __construct(
        private readonly CalendarService $calendarService,
    ) {}

    public function created(CalendarEvent $event): void
    {
        $this->flush($event);
    }

    public function updated(CalendarEvent $event): void
    {
        $this->flush($event);
    }

    public function deleted(CalendarEvent $event): void
    {
        $this->flush($event);
    }

    private function flush(CalendarEvent $event): void
    {
        $this->calendarService->flushCalendarCache(
            $event->school_id,
            $event->calendar_id,
            Carbon::parse((string) $event->starts_at),
        );
    }
}
