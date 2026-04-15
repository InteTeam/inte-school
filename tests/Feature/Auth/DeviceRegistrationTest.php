<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\RegisteredDevice;
use App\Models\School;
use App\Models\Scopes\SchoolScope;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DeviceRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->rootAdmin()->create(['email' => 'root@example.com']);
        $this->school = School::factory()->create();

        $this->user = User::factory()->create(['email' => 'user@example.com']);
        $this->school->users()->attach($this->user->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'teacher',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);
    }

    // --- Auth required ---

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/device-registration');

        $response->assertRedirect('/login');
    }

    public function test_registration_page_renders_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)->get('/device-registration');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Auth/DeviceRegistration'));
    }

    // --- Successful registration ---

    public function test_device_can_be_registered_with_push_subscription(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_school_id' => $this->school->id])
            ->post('/device-registration', [
                'device_name' => 'Chrome on MacBook',
                'device_fingerprint' => 'abc123fingerprint',
                'push_subscription' => [
                    'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
                    'keys' => [
                        'p256dh' => 'BNcRdreALRFXTkOOul6e1fVz76TRGYMBhmY-example',
                        'auth' => 'tBHItJI5svbpC7-example',
                    ],
                ],
            ]);

        $response->assertRedirect(route('dashboard'));

        $device = RegisteredDevice::withoutGlobalScope(SchoolScope::class)
            ->where('user_id', $this->user->id)
            ->first();

        $this->assertNotNull($device);
        $this->assertSame('Chrome on MacBook', $device->device_name);
        $this->assertSame('abc123fingerprint', $device->device_fingerprint);
        $this->assertSame($this->school->id, $device->school_id);
        $this->assertIsArray($device->push_subscription);
        $this->assertSame('https://fcm.googleapis.com/fcm/send/test', $device->push_subscription['endpoint']);
    }

    public function test_device_can_be_registered_without_push_subscription(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_school_id' => $this->school->id])
            ->post('/device-registration', [
                'device_name' => 'Safari on iPhone',
                'device_fingerprint' => 'xyz789fingerprint',
            ]);

        $response->assertRedirect(route('dashboard'));

        $device = RegisteredDevice::withoutGlobalScope(SchoolScope::class)
            ->where('user_id', $this->user->id)
            ->first();

        $this->assertNotNull($device);
        $this->assertSame('Safari on iPhone', $device->device_name);
        $this->assertNull($device->push_subscription);
    }

    // --- Fingerprint upsert ---

    public function test_same_fingerprint_updates_instead_of_creating_duplicate(): void
    {
        $this->actingAs($this->user)
            ->withSession(['current_school_id' => $this->school->id])
            ->post('/device-registration', [
                'device_name' => 'Chrome on MacBook',
                'device_fingerprint' => 'same-fingerprint',
            ]);

        $this->actingAs($this->user)
            ->withSession(['current_school_id' => $this->school->id])
            ->post('/device-registration', [
                'device_name' => 'Chrome on MacBook (updated)',
                'device_fingerprint' => 'same-fingerprint',
            ]);

        $count = RegisteredDevice::withoutGlobalScope(SchoolScope::class)
            ->where('user_id', $this->user->id)
            ->where('device_fingerprint', 'same-fingerprint')
            ->count();

        $this->assertSame(1, $count);

        $device = RegisteredDevice::withoutGlobalScope(SchoolScope::class)
            ->where('user_id', $this->user->id)
            ->where('device_fingerprint', 'same-fingerprint')
            ->first();

        $this->assertSame('Chrome on MacBook (updated)', $device->device_name);
    }

    public function test_different_fingerprints_create_separate_devices(): void
    {
        $this->actingAs($this->user)
            ->withSession(['current_school_id' => $this->school->id])
            ->post('/device-registration', [
                'device_name' => 'Chrome',
                'device_fingerprint' => 'fingerprint-one',
            ]);

        $this->actingAs($this->user)
            ->withSession(['current_school_id' => $this->school->id])
            ->post('/device-registration', [
                'device_name' => 'Safari',
                'device_fingerprint' => 'fingerprint-two',
            ]);

        $count = RegisteredDevice::withoutGlobalScope(SchoolScope::class)
            ->where('user_id', $this->user->id)
            ->count();

        $this->assertSame(2, $count);
    }

    // --- last_seen_at tracking ---

    public function test_last_seen_at_is_set_on_registration(): void
    {
        $this->actingAs($this->user)
            ->withSession(['current_school_id' => $this->school->id])
            ->post('/device-registration', [
                'device_name' => 'Chrome',
                'device_fingerprint' => 'tracking-fingerprint',
            ]);

        $device = RegisteredDevice::withoutGlobalScope(SchoolScope::class)
            ->where('user_id', $this->user->id)
            ->first();

        $this->assertNotNull($device->last_seen_at);
    }

    // --- Validation ---

    public function test_device_name_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_school_id' => $this->school->id])
            ->post('/device-registration', [
                'device_name' => '',
                'device_fingerprint' => 'abc123',
            ]);

        $response->assertSessionHasErrors('device_name');
    }

    public function test_device_name_max_length_is_100(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_school_id' => $this->school->id])
            ->post('/device-registration', [
                'device_name' => str_repeat('a', 101),
                'device_fingerprint' => 'abc123',
            ]);

        $response->assertSessionHasErrors('device_name');
    }

    public function test_device_fingerprint_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_school_id' => $this->school->id])
            ->post('/device-registration', [
                'device_name' => 'Chrome',
                'device_fingerprint' => '',
            ]);

        $response->assertSessionHasErrors('device_fingerprint');
    }

    public function test_push_subscription_endpoint_must_be_url(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_school_id' => $this->school->id])
            ->post('/device-registration', [
                'device_name' => 'Chrome',
                'device_fingerprint' => 'abc123',
                'push_subscription' => [
                    'endpoint' => 'not-a-url',
                    'keys' => [
                        'p256dh' => 'key-value',
                        'auth' => 'auth-value',
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('push_subscription.endpoint');
    }

    public function test_push_subscription_keys_are_required_when_subscription_present(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_school_id' => $this->school->id])
            ->post('/device-registration', [
                'device_name' => 'Chrome',
                'device_fingerprint' => 'abc123',
                'push_subscription' => [
                    'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
                    // missing keys
                ],
            ]);

        $response->assertSessionHasErrors('push_subscription.keys.p256dh');
        $response->assertSessionHasErrors('push_subscription.keys.auth');
    }

    // --- Disabled user ---

    public function test_disabled_user_cannot_register_device(): void
    {
        $disabled = User::factory()->disabled()->create(['email' => 'disabled@example.com']);

        $response = $this->actingAs($disabled)->get('/device-registration');

        $response->assertRedirect('/login');
    }
}
