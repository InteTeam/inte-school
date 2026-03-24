<?php

declare(strict_types=1);

namespace Tests\Feature\UserManagement;

use App\Models\GuardianStudent;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $rootAdmin;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootAdmin = User::factory()->rootAdmin()->create(['email' => 'root@example.com']);
        $this->school = School::factory()->create();

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->school->users()->attach($this->admin->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'admin',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);
    }

    // --- Staff invite ---

    public function test_staff_invite_creates_user_and_pivot(): void
    {
        $service = app(UserManagementService::class);

        $staff = $service->inviteStaff($this->school, [
            'name' => 'Jane Teacher',
            'email' => 'jane@example.com',
            'role' => 'teacher',
        ], $this->admin);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
        $this->assertDatabaseHas('school_user', [
            'user_id' => $staff->id,
            'school_id' => $this->school->id,
            'role' => 'teacher',
        ]);

        $pivot = DB::table('school_user')
            ->where('user_id', $staff->id)
            ->where('school_id', $this->school->id)
            ->first();

        $this->assertNotNull($pivot->invitation_token);
        $this->assertNull($pivot->accepted_at);
    }

    public function test_staff_invite_reuses_existing_user(): void
    {
        $existing = User::factory()->create(['email' => 'existing@example.com']);
        $service = app(UserManagementService::class);

        $invited = $service->inviteStaff($this->school, [
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'role' => 'support',
        ], $this->admin);

        $this->assertSame($existing->id, $invited->id);
        $this->assertDatabaseCount('users', 3); // rootAdmin + admin + existing
    }

    public function test_invitation_can_be_accepted_with_token(): void
    {
        $service = app(UserManagementService::class);

        $staff = $service->inviteStaff($this->school, [
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'role' => 'teacher',
        ], $this->admin);

        $token = DB::table('school_user')
            ->where('user_id', $staff->id)
            ->value('invitation_token');

        $result = $service->acceptInvitation($token, [
            'name' => 'Bob Teacher',
            'password' => 'SecurePass123!',
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'name' => 'Bob Teacher']);

        $pivot = DB::table('school_user')->where('user_id', $staff->id)->first();
        $this->assertNotNull($pivot->accepted_at);
        $this->assertNull($pivot->invitation_token);
    }

    public function test_expired_invitation_token_is_rejected(): void
    {
        $service = app(UserManagementService::class);

        $staff = $service->inviteStaff($this->school, [
            'name' => 'Expired User',
            'email' => 'expired@example.com',
            'role' => 'teacher',
        ], $this->admin);

        // Force expire the token
        DB::table('school_user')
            ->where('user_id', $staff->id)
            ->update(['invitation_expires_at' => now()->subDay()]);

        $token = DB::table('school_user')
            ->where('user_id', $staff->id)
            ->value('invitation_token');

        $result = $service->acceptInvitation($token, [
            'name' => 'Expired',
            'password' => 'SecurePass123!',
        ]);

        $this->assertFalse($result);
    }

    // --- Student enrolment ---

    public function test_student_enrolment_creates_user_with_student_role(): void
    {
        $service = app(UserManagementService::class);

        $student = $service->enrolStudent($this->school, [
            'name' => 'Alice Student',
            'email' => 'alice@example.com',
        ], $this->admin);

        $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);
        $this->assertDatabaseHas('school_user', [
            'user_id' => $student->id,
            'school_id' => $this->school->id,
            'role' => 'student',
        ]);
    }

    public function test_student_can_be_assigned_to_class_on_enrolment(): void
    {
        $this->actingAs($this->admin)->withSession(['current_school_id' => $this->school->id]);

        $class = SchoolClass::factory()->create(['school_id' => $this->school->id]);
        $service = app(UserManagementService::class);

        $student = $service->enrolStudent($this->school, [
            'name' => 'Bob Student',
            'email' => 'bob.student@example.com',
            'class_id' => $class->id,
        ], $this->admin);

        $this->assertDatabaseHas('class_students', [
            'class_id' => $class->id,
            'student_id' => $student->id,
        ]);
    }

    // --- Guardian linking ---

    public function test_guardian_can_be_linked_to_student(): void
    {
        $service = app(UserManagementService::class);

        $student = $service->enrolStudent($this->school, [
            'name' => 'Child',
            'email' => 'child@example.com',
        ], $this->admin);

        $guardian = User::factory()->create(['email' => 'parent@example.com']);
        $this->school->users()->attach($guardian->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'parent',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);

        $link = $service->linkGuardianToStudent($this->school, $guardian->id, $student->id, true);

        $this->assertDatabaseHas('guardian_student', [
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'school_id' => $this->school->id,
            'is_primary' => true,
        ]);
    }

    public function test_one_guardian_can_link_to_multiple_students(): void
    {
        $service = app(UserManagementService::class);

        $guardian = User::factory()->create(['email' => 'parent@example.com']);
        $this->school->users()->attach($guardian->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'parent',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);

        $child1 = $service->enrolStudent($this->school, ['name' => 'Child One', 'email' => 'child1@example.com'], $this->admin);
        $child2 = $service->enrolStudent($this->school, ['name' => 'Child Two', 'email' => 'child2@example.com'], $this->admin);

        $service->linkGuardianToStudent($this->school, $guardian->id, $child1->id, true);
        $service->linkGuardianToStudent($this->school, $guardian->id, $child2->id, false);

        $this->assertDatabaseCount('guardian_student', 2);
        $this->assertDatabaseHas('guardian_student', ['guardian_id' => $guardian->id, 'student_id' => $child1->id]);
        $this->assertDatabaseHas('guardian_student', ['guardian_id' => $guardian->id, 'student_id' => $child2->id]);
    }

    public function test_multiple_guardians_can_link_to_same_student(): void
    {
        $service = app(UserManagementService::class);

        $student = $service->enrolStudent($this->school, ['name' => 'Child', 'email' => 'child@example.com'], $this->admin);

        $parent1 = User::factory()->create(['email' => 'parent1@example.com']);
        $parent2 = User::factory()->create(['email' => 'parent2@example.com']);

        $this->school->users()->attach($parent1->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'parent',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);
        $this->school->users()->attach($parent2->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'parent',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);

        $service->linkGuardianToStudent($this->school, $parent1->id, $student->id, true);
        $service->linkGuardianToStudent($this->school, $parent2->id, $student->id, false);

        $links = GuardianStudent::where('student_id', $student->id)->count();
        $this->assertSame(2, $links);
    }

    // --- CSV injection sanitization ---

    public function test_csv_injection_characters_are_sanitized(): void
    {
        $service = app(UserManagementService::class);

        $row = [
            'name' => '=cmd|inject',
            'email' => '+inject@example.com',
            'extra' => '-foo',
            'another' => '@bar',
            'safe' => 'John Smith',
        ];

        $sanitized = $service->sanitizeCsvRow($row);

        $this->assertSame("'=cmd|inject", $sanitized['name']);
        $this->assertSame("'+inject@example.com", $sanitized['email']);
        $this->assertSame("'-foo", $sanitized['extra']);
        $this->assertSame("'@bar", $sanitized['another']);
        $this->assertSame('John Smith', $sanitized['safe']);
    }

    public function test_csv_import_parses_valid_csv(): void
    {
        $service = app(UserManagementService::class);

        $csv = "name,email,year_group\nAlice,alice@example.com,Year 3\nBob,bob@example.com,Year 4\n";
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $csv);
        rewind($handle);

        $rows = $service->parseCsvImport($handle);
        fclose($handle);

        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
        $this->assertSame('Year 4', $rows[1]['year_group']);
    }

    // --- Multi-tenant isolation ---

    public function test_guardian_link_is_scoped_to_school(): void
    {
        $otherSchool = School::factory()->create();
        $service = app(UserManagementService::class);

        $student = $service->enrolStudent($this->school, ['name' => 'Child', 'email' => 'child@example.com'], $this->admin);
        $guardian = User::factory()->create(['email' => 'guardian@example.com']);

        $service->linkGuardianToStudent($this->school, $guardian->id, $student->id);

        $this->actingAs($this->admin)->withSession(['current_school_id' => $otherSchool->id]);

        $visibleLinks = GuardianStudent::where('student_id', $student->id)->count();
        $this->assertSame(0, $visibleLinks);
    }
}
