<?php

declare(strict_types=1);

namespace Tests\Feature\FeatureRequests;

use App\Models\FeatureRequest;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class FeatureRequestTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $rootAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->school->users()->attach($this->admin->id, [
            'id' => Str::ulid(), 'role' => 'admin',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);

        $this->rootAdmin = User::factory()->create([
            'email' => 'root@example.com',
            'is_root_admin' => true,
        ]);
    }

    // --- Submission ---

    public function test_admin_can_submit_feature_request(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('admin.settings.feature-requests.store'), [
                'title' => 'Add bulk message export',
                'body' => 'It would be great to export message logs as CSV for our records.',
            ]);

        $response->assertRedirect(route('admin.settings.feature-requests'));
        $this->assertDatabaseHas('feature_requests', [
            'school_id' => $this->school->id,
            'submitted_by' => $this->admin->id,
            'title' => 'Add bulk message export',
            'status' => 'open',
        ]);
    }

    public function test_service_truncates_body_to_2000_chars(): void
    {
        // Call the service directly — controller validation runs first in HTTP context
        $longBody = str_repeat('a', 2500);

        $service = app(\App\Services\FeatureRequestService::class);
        $request = $service->submit($this->school, $this->admin, 'Long body test', $longBody);

        $this->assertSame(2000, mb_strlen($request->body));
    }

    public function test_body_max_2000_chars_enforced_by_validation(): void
    {
        $longBody = str_repeat('b', 2001);

        $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('admin.settings.feature-requests.store'), [
                'title' => 'Too long',
                'body' => $longBody,
            ])
            ->assertSessionHasErrors(['body']);
    }

    public function test_title_is_required(): void
    {
        $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('admin.settings.feature-requests.store'), [
                'title' => '',
                'body' => 'Some body text.',
            ])
            ->assertSessionHasErrors(['title']);
    }

    // --- Admin list (school-scoped) ---

    public function test_admin_sees_only_own_school_requests(): void
    {
        $otherSchool = School::factory()->create();
        $otherAdmin = User::factory()->create();
        $otherSchool->users()->attach($otherAdmin->id, [
            'id' => Str::ulid(), 'role' => 'admin',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);

        // Request from this school
        FeatureRequest::forceCreate([
            'school_id' => $this->school->id,
            'submitted_by' => $this->admin->id,
            'title' => 'Our request',
            'body' => 'Details.',
            'status' => 'open',
        ]);

        // Request from other school
        FeatureRequest::forceCreate([
            'school_id' => $otherSchool->id,
            'submitted_by' => $otherAdmin->id,
            'title' => 'Other school request',
            'body' => 'Other details.',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->get(route('admin.settings.feature-requests'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Settings/FeatureRequests')
                ->has('requests', 1)
                ->where('requests.0.title', 'Our request')
            );
    }

    public function test_requests_ordered_newest_first(): void
    {
        FeatureRequest::forceCreate([
            'school_id' => $this->school->id,
            'submitted_by' => $this->admin->id,
            'title' => 'Older request',
            'body' => 'Body.',
            'status' => 'open',
            'created_at' => now()->subHour(),
        ]);
        FeatureRequest::forceCreate([
            'school_id' => $this->school->id,
            'submitted_by' => $this->admin->id,
            'title' => 'Newer request',
            'body' => 'Body.',
            'status' => 'open',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->get(route('admin.settings.feature-requests'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('requests', 2)
                ->where('requests.0.title', 'Newer request')
                ->where('requests.1.title', 'Older request')
            );
    }

    // --- Root admin: cross-school feed ---

    public function test_root_admin_sees_all_schools_requests(): void
    {
        $otherSchool = School::factory()->create();

        FeatureRequest::forceCreate([
            'school_id' => $this->school->id,
            'submitted_by' => $this->admin->id,
            'title' => 'School A request',
            'body' => 'Body.',
            'status' => 'open',
        ]);
        FeatureRequest::forceCreate([
            'school_id' => $otherSchool->id,
            'submitted_by' => $this->admin->id,
            'title' => 'School B request',
            'body' => 'Body.',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->rootAdmin)
            ->get(route('root-admin.feature-requests.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('RootAdmin/FeatureRequests/Index')
                ->has('requests', 2)
            );
    }

    public function test_root_admin_can_update_request_status(): void
    {
        $request = FeatureRequest::forceCreate([
            'school_id' => $this->school->id,
            'submitted_by' => $this->admin->id,
            'title' => 'Add export feature',
            'body' => 'Body.',
            'status' => 'open',
        ]);

        $this->actingAs($this->rootAdmin)
            ->patch(route('root-admin.feature-requests.update-status', $request), [
                'status' => 'planned',
            ])
            ->assertRedirect(route('root-admin.feature-requests.index'));

        $this->assertDatabaseHas('feature_requests', [
            'id' => $request->id,
            'status' => 'planned',
        ]);
    }

    public function test_invalid_status_rejected(): void
    {
        $request = FeatureRequest::forceCreate([
            'school_id' => $this->school->id,
            'submitted_by' => $this->admin->id,
            'title' => 'Test',
            'body' => 'Body.',
            'status' => 'open',
        ]);

        $this->actingAs($this->rootAdmin)
            ->patch(route('root-admin.feature-requests.update-status', $request), [
                'status' => 'hacked',
            ])
            ->assertSessionHasErrors(['status']);
    }

    public function test_non_admin_cannot_submit_feature_request(): void
    {
        $teacher = User::factory()->create();
        $this->school->users()->attach($teacher->id, [
            'id' => Str::ulid(), 'role' => 'teacher',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('admin.settings.feature-requests.store'), [
                'title' => 'Sneaky request',
                'body' => 'Body.',
            ])
            ->assertStatus(403);
    }
}
