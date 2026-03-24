<?php

declare(strict_types=1);

namespace Tests\Feature\Statistics;

use App\Models\AttendanceRecord;
use App\Models\AttendanceRegister;
use App\Models\School;
use App\Models\SchoolApiKey;
use App\Models\SchoolClass;
use App\Models\Task;
use App\Models\User;
use App\Services\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

final class StatisticsApiTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create(['slug' => 'test-school']);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->school->users()->attach($this->admin->id, [
            'id' => Str::ulid(), 'role' => 'admin',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);
    }

    // --- Statistics aggregation ---

    public function test_attendance_stats_aggregate_correctly(): void
    {
        $teacher = User::factory()->create();
        $this->school->users()->attach($teacher->id, [
            'id' => Str::ulid(), 'role' => 'teacher',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);
        $student = User::factory()->create();

        $class = SchoolClass::forceCreate([
            'school_id' => $this->school->id,
            'name' => 'Year 7A',
            'year_group' => '7',
            'teacher_id' => $teacher->id,
        ]);

        $register = AttendanceRegister::forceCreate([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'register_date' => now()->toDateString(),
        ]);

        AttendanceRecord::forceCreate([
            'school_id' => $this->school->id,
            'register_id' => $register->id,
            'student_id' => $student->id,
            'marked_by' => $teacher->id,
            'status' => 'present',
            'marked_via' => 'manual',
            'pre_notified' => false,
        ]);
        AttendanceRecord::forceCreate([
            'school_id' => $this->school->id,
            'register_id' => $register->id,
            'student_id' => User::factory()->create()->id,
            'marked_by' => $teacher->id,
            'status' => 'absent',
            'marked_via' => 'manual',
            'pre_notified' => false,
        ]);

        $service = app(StatisticsService::class);
        $stats = $service->getAttendanceStats($this->school, now()->subWeek(), now());

        $this->assertSame(1, $stats['present']);
        $this->assertSame(1, $stats['absent']);
        $this->assertSame(2, $stats['total']);
        $this->assertSame(50.0, $stats['attendance_rate']);
    }

    public function test_homework_stats_aggregate_correctly(): void
    {
        Task::forceCreate([
            'school_id' => $this->school->id,
            'type' => 'homework',
            'title' => 'Maths Homework',
            'status' => 'done',
            'assigned_by_id' => $this->admin->id,
        ]);
        Task::forceCreate([
            'school_id' => $this->school->id,
            'type' => 'homework',
            'title' => 'Science Homework',
            'status' => 'todo',
            'assigned_by_id' => $this->admin->id,
        ]);
        Task::forceCreate([
            'school_id' => $this->school->id,
            'type' => 'homework',
            'title' => 'English Homework',
            'status' => 'done',
            'assigned_by_id' => $this->admin->id,
        ]);

        $service = app(StatisticsService::class);
        $stats = $service->getHomeworkStats($this->school, now()->subWeek(), now());

        $this->assertSame(2, $stats['done']);
        $this->assertSame(1, $stats['todo']);
        $this->assertEqualsWithDelta(66.7, $stats['completion_rate'], 0.1);
    }

    public function test_user_stats_count_by_role(): void
    {
        // admin already attached in setUp
        $teacher = User::factory()->create();
        $this->school->users()->attach($teacher->id, [
            'id' => Str::ulid(), 'role' => 'teacher',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);
        $parent = User::factory()->create();
        $this->school->users()->attach($parent->id, [
            'id' => Str::ulid(), 'role' => 'parent',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);

        $service = app(StatisticsService::class);
        $stats = $service->getUserStats($this->school);

        $this->assertSame(1, $stats['admin']);
        $this->assertSame(1, $stats['teacher']);
        $this->assertSame(1, $stats['parent']);
        $this->assertSame(3, $stats['total']);
    }

    public function test_dashboard_stats_are_cached(): void
    {
        $service = app(StatisticsService::class);

        $cacheKey = "school:{$this->school->id}:stats:dashboard:week";
        $this->assertFalse(Cache::has($cacheKey));

        $service->getDashboard($this->school, 'week');

        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_flush_cache_removes_all_periods(): void
    {
        $service = app(StatisticsService::class);

        foreach (['week', 'month', 'term'] as $period) {
            Cache::put("school:{$this->school->id}:stats:dashboard:{$period}", ['data'], 3600);
        }

        $service->flushCache($this->school);

        foreach (['week', 'month', 'term'] as $period) {
            $this->assertFalse(Cache::has("school:{$this->school->id}:stats:dashboard:{$period}"));
        }
    }

    // --- API key management ---

    public function test_admin_can_generate_api_key(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('admin.settings.api-keys.store'), [
                'name' => 'Council Integration',
                'permissions' => ['attendance', 'users'],
            ]);

        $response->assertRedirect(route('admin.settings.api-keys'));
        $this->assertDatabaseHas('school_api_keys', [
            'school_id' => $this->school->id,
            'name' => 'Council Integration',
        ]);

        // Raw key shown once via flash
        $response->assertSessionHas('generated_key');
    }

    public function test_api_key_stored_as_hash_not_plain_text(): void
    {
        $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('admin.settings.api-keys.store'), [
                'name' => 'Test Key',
                'permissions' => ['attendance'],
            ]);

        $keyRecord = SchoolApiKey::withoutGlobalScope(\App\Models\Scopes\SchoolScope::class)
            ->where('school_id', $this->school->id)
            ->first();

        $this->assertNotNull($keyRecord);
        // key_hash is SHA-256 hex — 64 chars, not the raw 40-char key
        $this->assertSame(64, strlen($keyRecord->key_hash));
    }

    public function test_admin_can_revoke_api_key(): void
    {
        $key = SchoolApiKey::factory()->create(['school_id' => $this->school->id]);

        $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->delete(route('admin.settings.api-keys.destroy', $key))
            ->assertRedirect(route('admin.settings.api-keys'));

        $this->assertDatabaseMissing('school_api_keys', ['id' => $key->id]);
    }

    // --- Stats API endpoint ---

    public function test_stats_api_returns_data_for_valid_key(): void
    {
        $rawKey = \Illuminate\Support\Str::random(40);
        SchoolApiKey::forceCreate([
            'school_id' => $this->school->id,
            'name' => 'Test',
            'key_hash' => hash('sha256', $rawKey),
            'permissions' => ['attendance', 'users'],
            'created_by' => $this->admin->id,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$rawKey}"])
            ->getJson(route('api.stats.index', ['schoolSlug' => 'test-school']));

        $response->assertOk()
            ->assertJsonStructure(['attendance', 'users', 'period', 'school']);
    }

    public function test_stats_api_rejects_missing_key(): void
    {
        $this->getJson(route('api.stats.index', ['schoolSlug' => 'test-school']))
            ->assertStatus(401);
    }

    public function test_stats_api_rejects_invalid_key(): void
    {
        $this->withHeaders(['Authorization' => 'Bearer invalid-key-here'])
            ->getJson(route('api.stats.index', ['schoolSlug' => 'test-school']))
            ->assertStatus(401);
    }

    public function test_stats_api_rejects_key_from_different_school(): void
    {
        $otherSchool = School::factory()->create(['slug' => 'other-school']);
        $rawKey = \Illuminate\Support\Str::random(40);
        SchoolApiKey::forceCreate([
            'school_id' => $otherSchool->id,
            'name' => 'Other',
            'key_hash' => hash('sha256', $rawKey),
            'permissions' => ['attendance'],
            'created_by' => $this->admin->id,
        ]);

        // Key belongs to other-school but request is for test-school
        $this->withHeaders(['Authorization' => "Bearer {$rawKey}"])
            ->getJson(route('api.stats.index', ['schoolSlug' => 'test-school']))
            ->assertStatus(404);
    }

    public function test_stats_api_respects_permissions(): void
    {
        $rawKey = \Illuminate\Support\Str::random(40);
        SchoolApiKey::forceCreate([
            'school_id' => $this->school->id,
            'name' => 'Attendance Only',
            'key_hash' => hash('sha256', $rawKey),
            'permissions' => ['attendance'], // no 'messages' or 'homework'
            'created_by' => $this->admin->id,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$rawKey}"])
            ->getJson(route('api.stats.index', ['schoolSlug' => 'test-school']));

        $response->assertOk();
        $data = $response->json();

        $this->assertArrayHasKey('attendance', $data);
        $this->assertArrayNotHasKey('messages', $data);
        $this->assertArrayNotHasKey('homework', $data);
    }

    public function test_stats_api_rejects_expired_key(): void
    {
        $rawKey = \Illuminate\Support\Str::random(40);
        SchoolApiKey::forceCreate([
            'school_id' => $this->school->id,
            'name' => 'Expired Key',
            'key_hash' => hash('sha256', $rawKey),
            'permissions' => ['attendance'],
            'created_by' => $this->admin->id,
            'expires_at' => now()->subDay(),
        ]);

        $this->withHeaders(['Authorization' => "Bearer {$rawKey}"])
            ->getJson(route('api.stats.index', ['schoolSlug' => 'test-school']))
            ->assertStatus(401);
    }
}
