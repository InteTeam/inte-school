<?php

declare(strict_types=1);

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use App\Models\School;
use App\Services\CalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CalendarController extends Controller
{
    public function __construct(
        private readonly CalendarService $calendarService,
    ) {}

    public function index(): InertiaResponse
    {
        $school = $this->currentSchool();

        $calendars = Calendar::query()
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'color', 'is_public']);

        $year = (int) request('year', now()->year);
        $month = (int) request('month', now()->month);

        $events = $this->calendarService->getMonthEvents($school, $year, $month);

        return Inertia::render('Admin/Calendar/Index', [
            'calendars' => $calendars,
            'events' => $events,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! $user->can('create', Calendar::class)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:internal,external,department,holiday'],
            'department_label' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_public' => ['boolean'],
        ]);

        $this->calendarService->createCalendar($this->currentSchool(), $validated);

        return redirect()->route('calendar.index')
            ->with(['alert' => __('calendar.created'), 'type' => 'success']);
    }

    public function destroy(Calendar $calendar): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! $user->can('create', Calendar::class)) {
            abort(403);
        }

        $this->calendarService->deleteCalendar($calendar);

        return redirect()->route('calendar.index')
            ->with(['alert' => __('calendar.deleted'), 'type' => 'success']);
    }

    private function currentSchool(): School
    {
        /** @var School $school */
        $school = School::find(session('current_school_id'));

        return $school;
    }
}
