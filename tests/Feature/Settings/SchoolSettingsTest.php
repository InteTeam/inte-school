<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\School;
use App\Models\SchoolLegalDocument;
use App\Models\User;
use App\Models\UserLegalAcceptance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolSettingsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $rootAdmin;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootAdmin = User::factory()->rootAdmin()->create(['email' => 'root@example.com']);
        $this->school = School::factory()->create([
            'notification_settings' => ['sms_fallback_enabled' => false, 'sms_timeout_seconds' => 900],
            'security_policy' => ['require_2fa' => false, 'session_timeout_minutes' => 480],
        ]);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->school->users()->attach($this->admin->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'admin',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);

        $this->fulfillLegalRequirements($this->admin);
    }

    private function fulfillLegalRequirements(User $user): void
    {
        // Reuse existing published docs if present, otherwise create them
        $types = ['privacy_policy', 'terms_conditions'];

        foreach ($types as $type) {
            $doc = SchoolLegalDocument::withoutGlobalScope(\App\Models\Scopes\SchoolScope::class)
                ->where('school_id', $this->school->id)
                ->where('type', $type)
                ->where('is_published', true)
                ->latest('published_at')
                ->first();

            if ($doc === null) {
                $doc = SchoolLegalDocument::forceCreate([
                    'school_id' => $this->school->id,
                    'type' => $type,
                    'content' => '<p>Content</p>',
                    'version' => '1.0',
                    'is_published' => true,
                    'published_at' => now(),
                    'published_by' => $this->rootAdmin->id,
                    'created_by' => $this->rootAdmin->id,
                ]);
            }

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

    private function schoolSession(): array
    {
        return ['current_school_id' => $this->school->id];
    }

    // --- General settings ---

    public function test_admin_can_view_general_settings(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->schoolSession())
            ->get(route('admin.settings.general'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Settings/General'));
    }

    public function test_admin_can_update_school_name(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->schoolSession())
            ->post(route('admin.settings.general.update'), [
                'name' => 'Updated School Name',
                'theme_config' => ['primary_color' => '#ff0000', 'dark_mode' => false],
            ]);

        $response->assertRedirect(route('admin.settings.general'));
        $this->assertDatabaseHas('schools', ['id' => $this->school->id, 'name' => 'Updated School Name']);
    }

    public function test_general_settings_validates_hex_color(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->schoolSession())
            ->post(route('admin.settings.general.update'), [
                'name' => 'Test School',
                'theme_config' => ['primary_color' => 'not-a-color'],
            ]);

        $response->assertSessionHasErrors('theme_config.primary_color');
    }

    // --- Notification settings ---

    public function test_admin_can_view_notification_settings(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->schoolSession())
            ->get(route('admin.settings.notifications'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Settings/Notifications'));
    }

    public function test_admin_can_enable_sms_fallback(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->schoolSession())
            ->put(route('admin.settings.notifications.update'), [
                'sms_fallback_enabled' => true,
            ]);

        $response->assertRedirect(route('admin.settings.notifications'));

        $updated = $this->school->fresh();
        $this->assertTrue((bool) ($updated->notification_settings['sms_fallback_enabled'] ?? false));
    }

    public function test_notification_settings_stored_in_jsonb_without_overwriting_other_keys(): void
    {
        $this->actingAs($this->admin)
            ->withSession($this->schoolSession())
            ->put(route('admin.settings.notifications.update'), [
                'sms_fallback_enabled' => true,
            ]);

        $updated = $this->school->fresh();
        // sms_timeout_seconds set in setUp should be preserved
        $this->assertSame(900, $updated->notification_settings['sms_timeout_seconds'] ?? null);
    }

    // --- Security settings ---

    public function test_admin_can_view_security_settings(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->schoolSession())
            ->get(route('admin.settings.security'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Settings/Security'));
    }

    public function test_admin_can_update_security_policy(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->schoolSession())
            ->put(route('admin.settings.security.update'), [
                'require_2fa' => true,
                'session_timeout_minutes' => 60,
            ]);

        $response->assertRedirect(route('admin.settings.security'));

        $updated = $this->school->fresh();
        $this->assertTrue((bool) ($updated->security_policy['require_2fa'] ?? false));
        $this->assertSame(60, $updated->security_policy['session_timeout_minutes'] ?? null);
    }

    public function test_security_tier_stored_in_jsonb(): void
    {
        $this->actingAs($this->admin)
            ->withSession($this->schoolSession())
            ->put(route('admin.settings.security.update'), [
                'require_2fa' => true,
                'session_timeout_minutes' => 120,
            ]);

        $raw = \DB::table('schools')->where('id', $this->school->id)->value('security_policy');
        $this->assertJson($raw);

        $decoded = json_decode($raw, true);
        $this->assertTrue($decoded['require_2fa']);
        $this->assertSame(120, $decoded['session_timeout_minutes']);
    }

    public function test_security_settings_validates_session_timeout_range(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->schoolSession())
            ->put(route('admin.settings.security.update'), [
                'require_2fa' => false,
                'session_timeout_minutes' => 5, // below minimum of 15
            ]);

        $response->assertSessionHasErrors('session_timeout_minutes');
    }

    // --- Legal documents page ---

    public function test_admin_can_view_legal_settings(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->schoolSession())
            ->get(route('admin.settings.legal'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Settings/Legal'));
    }

    // --- Access control ---

    public function test_teacher_cannot_access_settings(): void
    {
        $teacher = User::factory()->create(['email' => 'teacher@example.com']);
        $this->school->users()->attach($teacher->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'teacher',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);
        $this->fulfillLegalRequirements($teacher);

        $response = $this->actingAs($teacher)
            ->withSession($this->schoolSession())
            ->get(route('admin.settings.general'));

        $response->assertStatus(403);
    }

    // --- SOP: Guest redirect ---

    public function test_guest_cannot_access_settings(): void
    {
        $this->get(route('admin.settings.general'))->assertRedirect('/login');
    }

    // --- SOP: Multi-tenant isolation ---

    public function test_settings_scoped_to_school(): void
    {
        // Update school A settings
        $this->actingAs($this->admin)
            ->withSession($this->schoolSession())
            ->put(route('admin.settings.notifications.update'), [
                'sms_fallback_enabled' => true,
            ]);

        // School B should have its own independent defaults (not affected by school A update)
        $otherSchool = School::factory()->create();
        $otherSetting = $otherSchool->getNotificationSetting('sms_fallback_enabled');
        // School B should NOT have the value we just set on school A
        $this->assertNotTrue($otherSetting);
    }
}
