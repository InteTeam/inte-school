<?php

declare(strict_types=1);

namespace Tests\Feature\Attendance;

use App\Jobs\SendAttendanceAlertJob;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRegister;
use App\Models\HardwareDeviceToken;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Scopes\SchoolScope;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $teacher;

    private User $parent;

    private User $student;

    private SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create([
            'notification_settings' => ['sms_fallback_enabled' => false],
        ]);

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

        $this->student = User::factory()->create(['email' => 'student@example.com']);
        $this->school->users()->attach($this->student->id, [
            'id' => Str::ulid(), 'role' => 'student',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);

        $this->class = SchoolClass::forceCreate([
            'school_id' => $this->school->id,
            'name' => 'Class 1A',
            'year_group' => 'Year 1',
            'teacher_id' => $this->teacher->id,
        ]);

        \DB::table('class_students')->insert([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'enrolled_at' => now(),
        ]);

        \DB::table('guardian_student')->insert([
            'id' => (string) Str::ulid(),
            'school_id' => $this->school->id,
            'guardian_id' => $this->parent->id,
            'student_id' => $this->student->id,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // --- Teacher marks register ---

    public function test_teacher_can_mark_student_present(): void
    {
        $service = app(AttendanceService::class);
        $register = $service->openOrGetRegister($this->school, $this->teacher, $this->class->id, now());

        $record = $service->mark($register, $this->student->id, 'present', $this->teacher);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
            'student_id' => $this->student->id,
            'status' => 'present',
            'marked_via' => 'manual',
        ]);
    }

    public function test_teacher_can_mark_student_absent(): void
    {
        Queue::fake();
        $service = app(AttendanceService::class);
        $register = $service->openOrGetRegister($this->school, $this->teacher, $this->class->id, now());

        $record = $service->mark($register, $this->student->id, 'absent', $this->teacher);

        $this->assertSame('absent', $record->status);
    }

    public function test_teacher_can_mark_student_late(): void
    {
        $service = app(AttendanceService::class);
        $register = $service->openOrGetRegister($this->school, $this->teacher, $this->class->id, now());

        $record = $service->mark($register, $this->student->id, 'late', $this->teacher);

        $this->assertSame('late', $record->status);
    }

    // --- Admin marks on behalf ---

    public function test_admin_can_open_register_on_behalf(): void
    {
        $service = app(AttendanceService::class);
        $register = $service->openOrGetRegister($this->school, $this->admin, $this->class->id, now());

        $this->assertDatabaseHas('attendance_registers', [
            'id' => $register->id,
            'class_id' => $this->class->id,
        ]);
    }

    public function test_admin_can_mark_student_via_override_route(): void
    {
        $service = app(AttendanceService::class);
        $register = $service->openOrGetRegister($this->school, $this->teacher, $this->class->id, now());

        $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('admin.attendance.override'), [
                'register_id' => $register->id,
                'student_id' => $this->student->id,
                'status' => 'present',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $this->student->id,
            'status' => 'present',
            'marked_by' => $this->admin->id,
        ]);
    }

    // --- Hardware API endpoint ---

    public function test_hardware_api_marks_attendance_via_nfc_card(): void
    {
        $rawToken = Str::random(32);
        HardwareDeviceToken::forceCreate([
            'school_id' => $this->school->id,
            'name' => 'Main Entrance Reader',
            'token_hash' => hash('sha256', $rawToken),
        ]);

        // Give student an NFC card
        $this->student->update(['nfc_card_id' => 'CARD-001']);

        // Register student in a class so the hardware controller can open a register
        $response = $this->postJson(route('api.attendance.mark'), [
            'device_token' => $rawToken,
            'card_id' => 'CARD-001',
            'school_id' => $this->school->id,
            'timestamp' => now()->toIso8601String(),
        ]);

        $response->assertOk()->assertJsonFragment(['attendance_status' => 'present']);

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $this->student->id,
            'status' => 'present',
            'marked_via' => 'nfc_card',
        ]);
    }

    public function test_hardware_api_rejects_invalid_token(): void
    {
        $this->postJson(route('api.attendance.mark'), [
            'device_token' => 'invalid-token',
            'card_id' => 'CARD-001',
            'school_id' => $this->school->id,
            'timestamp' => now()->toIso8601String(),
        ])->assertStatus(401);
    }

    // --- Pre-notification suppresses alert ---

    public function test_pre_notification_suppresses_absence_alert(): void
    {
        Queue::fake();
        $service = app(AttendanceService::class);
        $register = $service->openOrGetRegister($this->school, $this->teacher, $this->class->id, now());

        $service->mark($register, $this->student->id, 'absent', $this->teacher, 'manual', null, preNotified: true);

        Queue::assertNotPushed(SendAttendanceAlertJob::class);
    }

    public function test_absent_without_pre_notification_dispatches_alert(): void
    {
        Queue::fake();
        $service = app(AttendanceService::class);
        $register = $service->openOrGetRegister($this->school, $this->teacher, $this->class->id, now());

        $service->mark($register, $this->student->id, 'absent', $this->teacher);

        Queue::assertPushed(SendAttendanceAlertJob::class);
    }

    // --- Stats caching ---

    public function test_daily_stats_are_cached(): void
    {
        $service = app(AttendanceService::class);
        $register = $service->openOrGetRegister($this->school, $this->teacher, $this->class->id, now());
        $service->mark($register, $this->student->id, 'present', $this->teacher);

        $stats = $service->getDailyStats($this->school, now());

        $this->assertSame(1, $stats['present']);
        $this->assertTrue(Cache::has("school:{$this->school->id}:attendance:" . now()->toDateString()));
    }

    public function test_cache_invalidated_on_mark(): void
    {
        $cacheKey = "school:{$this->school->id}:attendance:" . now()->toDateString();
        Cache::put($cacheKey, ['present' => 0, 'absent' => 0, 'late' => 0, 'date' => now()->toDateString()], 3600);

        $service = app(AttendanceService::class);
        $register = $service->openOrGetRegister($this->school, $this->teacher, $this->class->id, now());
        $service->mark($register, $this->student->id, 'absent', $this->teacher);

        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_daily_aggregate_is_correct(): void
    {
        $service = app(AttendanceService::class);
        $register = $service->openOrGetRegister($this->school, $this->teacher, $this->class->id, now());

        $service->mark($register, $this->student->id, 'present', $this->teacher);

        // Add a second student
        $student2 = User::factory()->create();
        \DB::table('class_students')->insert([
            'class_id' => $this->class->id,
            'student_id' => $student2->id,
            'school_id' => $this->school->id,
            'enrolled_at' => now(),
        ]);
        $service->mark($register, $student2->id, 'absent', $this->teacher);

        $stats = $service->getDailyStats($this->school, now());

        $this->assertSame(1, $stats['present']);
        $this->assertSame(1, $stats['absent']);
        $this->assertSame(0, $stats['late']);
    }
}
