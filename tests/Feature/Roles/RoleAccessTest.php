<?php

declare(strict_types=1);

namespace Tests\Feature\Roles;

use App\Models\School;
use App\Models\SchoolLegalDocument;
use App\Models\User;
use App\Models\UserLegalAcceptance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $rootAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootAdmin = User::factory()->rootAdmin()->create(['email' => 'root@example.com']);
        $this->school = School::factory()->create();
    }

    private function createUserWithRole(string $role, string $email = 'user@example.com'): User
    {
        $user = User::factory()->create(['email' => $email]);
        $this->school->users()->attach($user->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => $role,
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);

        return $user;
    }

    private function withSchoolSession(User $user): self
    {
        $this->actingAs($user)
            ->withSession(['current_school_id' => $this->school->id]);

        return $this;
    }

    private function fulfillLegalRequirements(User $user): void
    {
        // Create published legal docs and record acceptance to pass EnsureLegalAcceptance
        $privacyDoc = SchoolLegalDocument::forceCreate([
            'school_id' => $this->school->id,
            'type' => 'privacy_policy',
            'content' => '<p>Privacy</p>',
            'version' => '1.0',
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $this->rootAdmin->id,
            'created_by' => $this->rootAdmin->id,
        ]);
        $termsDoc = SchoolLegalDocument::forceCreate([
            'school_id' => $this->school->id,
            'type' => 'terms_conditions',
            'content' => '<p>Terms</p>',
            'version' => '1.0',
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $this->rootAdmin->id,
            'created_by' => $this->rootAdmin->id,
        ]);

        foreach ([$privacyDoc, $termsDoc] as $doc) {
            UserLegalAcceptance::forceCreate([
                'school_id' => $this->school->id,
                'user_id' => $user->id,
                'document_id' => $doc->id,
                'document_type' => $doc->type,
                'document_version' => $doc->version,
                'accepted_at' => now(),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test',
                'created_at' => now(),
            ]);
        }
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = $this->createUserWithRole('admin', 'admin@example.com');
        $this->fulfillLegalRequirements($admin);

        $response = $this->actingAs($admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Dashboard'));
    }

    public function test_teacher_can_access_teacher_dashboard(): void
    {
        $teacher = $this->createUserWithRole('teacher', 'teacher@example.com');
        $this->fulfillLegalRequirements($teacher);

        $response = $this->actingAs($teacher)
            ->withSession(['current_school_id' => $this->school->id])
            ->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Teacher/Dashboard'));
    }

    public function test_teacher_cannot_access_admin_routes(): void
    {
        $teacher = $this->createUserWithRole('teacher', 'teacher@example.com');
        $this->fulfillLegalRequirements($teacher);

        $response = $this->actingAs($teacher)
            ->withSession(['current_school_id' => $this->school->id])
            ->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_admin_cannot_access_teacher_routes(): void
    {
        $admin = $this->createUserWithRole('admin', 'admin@example.com');
        $this->fulfillLegalRequirements($admin);

        $response = $this->actingAs($admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->get('/teacher/dashboard');

        $response->assertStatus(403);
    }

    public function test_root_admin_can_access_any_role_route(): void
    {
        $response = $this->actingAs($this->rootAdmin)
            ->withSession(['current_school_id' => $this->school->id])
            ->get('/admin/dashboard');

        $response->assertStatus(200);
    }

    public function test_user_role_is_correctly_stored_in_school_user_pivot(): void
    {
        $user = $this->createUserWithRole('support', 'support@example.com');

        $this->withSession(['current_school_id' => $this->school->id]);

        $this->actingAs($user);
        $role = $user->currentSchoolRole();

        $this->assertSame('support', $role);
    }

    public function test_user_not_in_school_cannot_access_school_routes(): void
    {
        // User with no school membership
        User::factory()->rootAdmin()->create(['email' => 'other-root@example.com']); // ensure root admin already exists
        $outsider = User::factory()->create(['email' => 'outsider@example.com']);

        $response = $this->actingAs($outsider)
            ->withSession(['current_school_id' => $this->school->id])
            ->get('/admin/dashboard');

        // EnsureSchoolContext redirects non-members to login
        $response->assertRedirect('/login');
    }
}
