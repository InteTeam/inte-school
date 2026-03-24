<?php

declare(strict_types=1);

namespace Tests\Feature\Calendar;

use App\Models\Calendar;
use App\Models\CalendarEvent;
use App\Models\School;
use App\Models\User;
use App\Services\CalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

final class CalendarTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $teacher;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->school->users()->attach($this->admin->id, [
            'id' => Str::ulid(), 'role' => 'admin',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);

        $this->teacher = User::factory()->create(['email' => 'teacher@example.com']);
        $this->school->users()->attach($this->teacher->id, [
            'id' => Str::ulid(), 'role' => 'teacher',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);

        $this->parent = User::factory()->create(['email' => 'parent@example.com']);
        $this->school->users()->attach($this->parent->id, [
            'id' => Str::ulid(), 'role' => 'parent',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);
    }

    // --- Calendar CRUD (4 types) ---

    public function test_admin_can_create_internal_calendar(): void
    {
        $service = app(CalendarService::class);
        $calendar = $service->createCalendar($this->school, [
            'name' => 'School Events',
            'type' => 'internal',
            'color' => '#3b82f6',
            'is_public' => false,
        ]);

        $this->assertDatabaseHas('calendars', ['id' => $calendar->id, 'type' => 'internal']);
    }

    public function test_admin_can_create_external_calendar(): void
    {
        $service = app(CalendarService::class);
        $calendar = $service->createCalendar($this->school, [
            'name' => 'Holiday Calendar',
            'type' => 'external',
            'is_public' => true,
        ]);

        $this->assertTrue($calendar->is_public);
        $this->assertDatabaseHas('calendars', ['type' => 'external', 'is_public' => true]);
    }

    public function test_admin_can_create_department_calendar(): void
    {
        $service = app(CalendarService::class);
        $calendar = $service->createCalendar($this->school, [
            'name' => 'Maths Department',
            'type' => 'department',
            'department_label' => 'Mathematics',
        ]);

        $this->assertSame('Mathematics', $calendar->department_label);
    }

    public function test_admin_can_create_holiday_calendar(): void
    {
        $service = app(CalendarService::class);
        $calendar = $service->createCalendar($this->school, [
            'name' => 'Term Dates',
            'type' => 'holiday',
            'is_public' => true,
        ]);

        $this->assertDatabaseHas('calendars', ['type' => 'holiday']);
    }

    // --- Event CRUD ---

    public function test_admin_can_create_calendar_event(): void
    {
        $service = app(CalendarService::class);
        $calendar = $service->createCalendar($this->school, ['name' => 'School Events', 'type' => 'internal']);

        $event = $service->createEvent($calendar, $this->admin, [
            'title' => 'Sports Day',
            'starts_at' => now()->addDays(7),
            'ends_at' => now()->addDays(7)->addHours(4),
        ]);

        $this->assertDatabaseHas('calendar_events', [
            'id' => $event->id,
            'title' => 'Sports Day',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_teacher_can_create_event(): void
    {
        $service = app(CalendarService::class);
        $calendar = $service->createCalendar($this->school, ['name' => 'Dept', 'type' => 'department']);

        $event = $service->createEvent($calendar, $this->teacher, [
            'title' => 'Class Trip',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(6),
        ]);

        $this->assertSame($this->teacher->id, $event->created_by);
    }

    public function test_teacher_can_update_own_event(): void
    {
        $service = app(CalendarService::class);
        $calendar = $service->createCalendar($this->school, ['name' => 'Dept', 'type' => 'department']);
        $event = $service->createEvent($calendar, $this->teacher, [
            'title' => 'Original Title',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(1),
        ]);

        $updated = $service->updateEvent($event, ['title' => 'Updated Title']);

        $this->assertSame('Updated Title', $updated->title);
    }

    public function test_teacher_cannot_update_another_teachers_event(): void
    {
        $teacher2 = User::factory()->create(['email' => 'teacher2@example.com']);
        $this->school->users()->attach($teacher2->id, [
            'id' => Str::ulid(), 'role' => 'teacher',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);

        $service = app(CalendarService::class);
        $calendar = $service->createCalendar($this->school, ['name' => 'Dept', 'type' => 'department']);
        $event = $service->createEvent($calendar, $teacher2, [
            'title' => 'Teacher 2 event',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(1),
        ]);

        $this->actingAs($this->teacher)->withSession(['current_school_id' => $this->school->id]);
        $this->assertFalse($this->teacher->can('update', $event));
    }

    // --- External calendar (parents) ---

    public function test_parent_can_view_public_calendar_events(): void
    {
        $service = app(CalendarService::class);
        $publicCalendar = $service->createCalendar($this->school, [
            'name' => 'Holidays',
            'type' => 'holiday',
            'is_public' => true,
        ]);
        $privateCalendar = $service->createCalendar($this->school, [
            'name' => 'Internal',
            'type' => 'internal',
            'is_public' => false,
        ]);

        $service->createEvent($publicCalendar, $this->admin, [
            'title' => 'Easter Holiday',
            'starts_at' => now()->startOfMonth(),
            'ends_at' => now()->startOfMonth()->addDays(2),
        ]);

        $service->createEvent($privateCalendar, $this->admin, [
            'title' => 'Staff Meeting',
            'starts_at' => now()->startOfMonth()->addDays(3),
            'ends_at' => now()->startOfMonth()->addDays(3)->addHours(1),
        ]);

        $events = $service->getPublicMonthEvents($this->school, now()->year, now()->month);

        $titles = array_column($events, 'title');
        $this->assertContains('Easter Holiday', $titles);
        $this->assertNotContains('Staff Meeting', $titles);
    }

    // --- Cache invalidation ---

    public function test_cache_invalidated_on_event_create(): void
    {
        $service = app(CalendarService::class);
        $calendar = $service->createCalendar($this->school, ['name' => 'Events', 'type' => 'internal']);

        $cacheKey = "school:{$this->school->id}:calendar:all:" . now()->year . '-' . now()->month;
        Cache::put($cacheKey, ['cached_data'], 3600);

        $service->createEvent($calendar, $this->admin, [
            'title' => 'New Event',
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);

        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_cache_invalidated_on_event_update(): void
    {
        $service = app(CalendarService::class);
        $calendar = $service->createCalendar($this->school, ['name' => 'Events', 'type' => 'internal']);
        $event = $service->createEvent($calendar, $this->admin, [
            'title' => 'Test Event',
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);

        $cacheKey = "school:{$this->school->id}:calendar:all:" . now()->year . '-' . now()->month;
        Cache::put($cacheKey, ['cached_data'], 3600);

        $service->updateEvent($event, ['title' => 'Updated']);

        $this->assertFalse(Cache::has($cacheKey));
    }

    // --- Sort order (documented exception: starts_at ASC) ---

    public function test_events_ordered_by_starts_at_ascending(): void
    {
        $service = app(CalendarService::class);
        $calendar = $service->createCalendar($this->school, ['name' => 'Events', 'type' => 'internal']);

        $later = now()->addDays(3)->startOfDay();
        $earlier = now()->addDays(1)->startOfDay();

        $service->createEvent($calendar, $this->admin, [
            'title' => 'Later Event',
            'starts_at' => $later,
            'ends_at' => $later->copy()->addHour(),
        ]);

        $service->createEvent($calendar, $this->admin, [
            'title' => 'Earlier Event',
            'starts_at' => $earlier,
            'ends_at' => $earlier->copy()->addHour(),
        ]);

        $events = $service->getMonthEvents($this->school, now()->year, now()->month);

        $this->assertSame('Earlier Event', $events[0]['title']);
        $this->assertSame('Later Event', $events[1]['title']);
    }
}
