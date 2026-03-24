<?php

declare(strict_types=1);

namespace Tests\Feature\RootAdmin;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RootAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_registered_user_becomes_root_admin_automatically(): void
    {
        $this->assertSame(0, User::count());

        $user = User::factory()->create(['email' => 'first@example.com']);

        $this->assertTrue($user->fresh()->isRootAdmin());
    }

    public function test_second_user_does_not_become_root_admin(): void
    {
        User::factory()->create(['email' => 'first@example.com']);
        $second = User::factory()->create(['email' => 'second@example.com']);

        $this->assertFalse($second->fresh()->isRootAdmin());
    }

    public function test_root_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->rootAdmin()->create();

        $response = $this->actingAs($admin)->get('/root-admin');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('RootAdmin/Dashboard'));
    }

    public function test_root_admin_can_access_schools_list(): void
    {
        $admin = User::factory()->rootAdmin()->create();
        School::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get('/root-admin/schools');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('RootAdmin/Schools/Index')
            ->has('schools', 3)
        );
    }

    public function test_non_root_admin_cannot_access_root_admin_dashboard(): void
    {
        // First create a root admin so the next user doesn't auto-get root admin
        User::factory()->rootAdmin()->create(['email' => 'root@example.com']);
        $user = User::factory()->create(['email' => 'regular@example.com']);

        $response = $this->actingAs($user)->get('/root-admin');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_root_admin_routes(): void
    {
        $response = $this->get('/root-admin');

        $response->assertRedirect('/login');
    }

    public function test_root_admin_dashboard_includes_platform_stats(): void
    {
        $admin = User::factory()->rootAdmin()->create();
        School::factory()->count(2)->create();
        School::factory()->inactive()->count(1)->create();

        $response = $this->actingAs($admin)->get('/root-admin');

        $response->assertInertia(fn ($page) => $page
            ->component('RootAdmin/Dashboard')
            ->has('stats.school_count')
            ->has('stats.active_school_count')
            ->has('stats.user_count')
            ->where('stats.active_school_count', 2)
        );
    }
}
