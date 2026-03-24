<?php

declare(strict_types=1);

namespace Tests\Feature\School;

use App\Models\RegisteredDevice;
use App\Models\School;
use App\Models\User;
use App\Services\SchoolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_can_be_created_with_defaults(): void
    {
        $school = School::factory()->create(['name' => 'Test Academy']);

        $this->assertDatabaseHas('schools', ['name' => 'Test Academy']);
        $this->assertTrue($school->isActive());
        $this->assertFalse($school->rag_enabled);
        $this->assertIsArray($school->settings);
        $this->assertIsArray($school->notification_settings);
    }

    public function test_school_can_be_soft_deleted(): void
    {
        $school = School::factory()->create();

        $school->delete();

        $this->assertSoftDeleted('schools', ['id' => $school->id]);
        $this->assertNull(School::find($school->id));
        $this->assertNotNull(School::withTrashed()->find($school->id));
    }

    public function test_school_settings_can_be_updated(): void
    {
        $school = School::factory()->create(['settings' => ['theme' => 'light']]);

        $service = app(SchoolService::class);
        $service->updateSettings($school, ['max_file_size_mb' => 10]);

        $school->refresh();

        $this->assertSame('light', $school->getSetting('theme'));
        $this->assertSame(10, $school->getSetting('max_file_size_mb'));
    }

    public function test_school_notification_settings_can_be_updated(): void
    {
        $school = School::factory()->create();

        $service = app(SchoolService::class);
        $service->updateNotificationSettings($school, ['sms_fallback_enabled' => true, 'sms_timeout_seconds' => 1200]);

        $school->refresh();

        $this->assertTrue((bool) $school->getNotificationSetting('sms_fallback_enabled'));
        $this->assertSame(1200, $school->getNotificationSetting('sms_timeout_seconds'));
    }

    public function test_has_school_scope_isolates_data_between_schools(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $user = User::factory()->create();

        // Insert devices directly bypassing scope
        RegisteredDevice::forceCreate([
            'user_id' => $user->id,
            'school_id' => $schoolA->id,
            'device_name' => 'Phone A',
            'device_fingerprint' => 'fp-a',
            'last_seen_at' => now(),
        ]);
        RegisteredDevice::forceCreate([
            'user_id' => $user->id,
            'school_id' => $schoolB->id,
            'device_name' => 'Phone B',
            'device_fingerprint' => 'fp-b',
            'last_seen_at' => now(),
        ]);

        // Authenticate so SchoolScope is active
        $this->actingAs($user);

        // In school B context, only school B's device is visible
        $this->withSession(['current_school_id' => $schoolB->id]);
        $devices = RegisteredDevice::all();
        $this->assertCount(1, $devices);
        $this->assertSame('Phone B', $devices->first()->device_name);

        // Switch to school A context
        session(['current_school_id' => $schoolA->id]);
        $devices = RegisteredDevice::all();
        $this->assertCount(1, $devices);
        $this->assertSame('Phone A', $devices->first()->device_name);
    }

    public function test_root_admin_can_bypass_school_scope(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $user = User::factory()->create();

        // Create devices in both schools directly (bypassing scope)
        RegisteredDevice::forceCreate([
            'user_id' => $user->id,
            'school_id' => $schoolA->id,
            'device_name' => 'Phone A',
            'device_fingerprint' => 'fp-a',
            'last_seen_at' => now(),
        ]);
        RegisteredDevice::forceCreate([
            'user_id' => $user->id,
            'school_id' => $schoolB->id,
            'device_name' => 'Phone B',
            'device_fingerprint' => 'fp-b',
            'last_seen_at' => now(),
        ]);

        session(['current_school_id' => $schoolA->id]);

        // Root admin bypasses scope using scopeForSchool
        $allDevices = RegisteredDevice::withoutGlobalScopes()->get();
        $this->assertCount(2, $allDevices);
    }

    public function test_rag_feature_flag_is_controlled_by_column(): void
    {
        $school = School::factory()->create(['rag_enabled' => false]);
        $this->assertFalse($school->isFeatureEnabled('rag'));

        $school->rag_enabled = true;
        $school->save();

        $this->assertTrue($school->isFeatureEnabled('rag'));
    }

    public function test_custom_feature_flags_are_stored_in_settings(): void
    {
        $school = School::factory()->withFeature('whatsapp')->create();

        $this->assertTrue($school->isFeatureEnabled('whatsapp'));
        $this->assertFalse($school->isFeatureEnabled('sms_bulk'));
    }

    public function test_observer_flushes_cache_on_school_save(): void
    {
        $school = School::factory()->create();

        cache()->put("school:{$school->id}:settings", ['cached' => true], 3600);
        cache()->put("school:{$school->id}:features", ['cached' => true], 3600);
        cache()->put("school:{$school->id}:notification_settings", ['cached' => true], 3600);

        $school->name = 'Updated Name';
        $school->save();

        $this->assertNull(cache()->get("school:{$school->id}:settings"));
        $this->assertNull(cache()->get("school:{$school->id}:features"));
        $this->assertNull(cache()->get("school:{$school->id}:notification_settings"));
    }
}
