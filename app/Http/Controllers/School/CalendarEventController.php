<?php

declare(strict_types=1);

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use App\Models\CalendarEvent;
use App\Models\School;
use App\Models\Scopes\SchoolScope;
use App\Services\CalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CalendarEventController extends Controller
{
    public function __construct(
        private readonly CalendarService $calendarService,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! $user->can('create', CalendarEvent::class)) {
            abort(403);
        }

        $validated = $request->validate([
            'calendar_id' => ['required', 'string'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'all_day' => ['boolean'],
            'location' => ['nullable', 'string', 'max:200'],
        ]);

        $calendar = Calendar::withoutGlobalScope(SchoolScope::class)
            ->findOrFail($validated['calendar_id']);

        $this->calendarService->createEvent($calendar, $user, $validated);

        return redirect()->route('calendar.index')
            ->with(['alert' => __('calendar.event_created'), 'type' => 'success']);
    }

    public function update(Request $request, CalendarEvent $calendarEvent): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! $user->can('update', $calendarEvent)) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'all_day' => ['boolean'],
            'location' => ['nullable', 'string', 'max:200'],
        ]);

        $this->calendarService->updateEvent($calendarEvent, $validated);

        return redirect()->route('calendar.index')
            ->with(['alert' => __('calendar.event_updated'), 'type' => 'success']);
    }

    public function destroy(CalendarEvent $calendarEvent): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! $user->can('delete', $calendarEvent)) {
            abort(403);
        }

        $this->calendarService->deleteEvent($calendarEvent);

        return redirect()->route('calendar.index')
            ->with(['alert' => __('calendar.event_deleted'), 'type' => 'success']);
    }

    public function externalIndex(): InertiaResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! $user->can('viewExternal', CalendarEvent::class)) {
            abort(403);
        }

        $school = $this->currentSchool();
        $year = (int) request('year', now()->year);
        $month = (int) request('month', now()->month);

        $events = $this->calendarService->getPublicMonthEvents($school, $year, $month);

        return Inertia::render('Parent/Calendar/Index', [
            'events' => $events,
            'year' => $year,
            'month' => $month,
        ]);
    }

    private function currentSchool(): School
    {
        /** @var School $school */
        $school = School::find(session('current_school_id'));

        return $school;
    }
}
