<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\School;
use App\Models\SchoolApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests rate limiting on key endpoints.
 *
 * Current implementation:
 * - API stats endpoint: throttle:60,1 (60 requests per minute)
 * - Hardware attendance endpoint: throttle:60,1
 *
 * Documented but NOT YET implemented (CLAUDE.md):
 * - Login: 10/min/IP
 * - Password reset: 5/min/IP
 * - 2FA: 5/min/user
 *
 * Tests below verify what IS implemented. The missing rate limiters
 * are flagged as gaps to add during Phase 5 (Hardening).
 */
final class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create(['slug' => 'rate-test']);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->school->users()->attach($this->admin->id, [
            'id' => Str::ulid(),
            'role' => 'admin',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);
    }

    // --- API stats endpoint throttle ---

    public function test_api_stats_endpoint_allows_requests_within_limit(): void
    {
        $rawKey = Str::random(40);
        SchoolApiKey::forceCreate([
            'school_id' => $this->school->id,
            'name' => 'Rate Test',
            'key_hash' => hash('sha256', $rawKey),
            'permissions' => ['attendance'],
            'created_by' => $this->admin->id,
        ]);

        // First request should succeed
        $response = $this->withHeaders(['Authorization' => "Bearer {$rawKey}"])
            ->getJson(route('api.stats.index', ['schoolSlug' => 'rate-test']));

        $response->assertOk();
    }

    public function test_api_stats_endpoint_returns_429_when_limit_exceeded(): void
    {
        $rawKey = Str::random(40);
        SchoolApiKey::forceCreate([
            'school_id' => $this->school->id,
            'name' => 'Rate Test',
            'key_hash' => hash('sha256', $rawKey),
            'permissions' => ['attendance'],
            'created_by' => $this->admin->id,
        ]);

        $headers = ['Authorization' => "Bearer {$rawKey}"];
        $route = route('api.stats.index', ['schoolSlug' => 'rate-test']);

        // Send 60 requests (the limit)
        for ($i = 0; $i < 60; $i++) {
            $this->withHeaders($headers)->getJson($route);
        }

        // 61st request should be throttled
        $response = $this->withHeaders($headers)->getJson($route);
        $response->assertStatus(429);
    }

    // --- Hardware attendance endpoint throttle ---

    public function test_hardware_attendance_allows_requests_within_limit(): void
    {
        $response = $this->postJson(route('api.attendance.mark'), [
            'nfc_card_id' => 'test-card',
            'device_token' => 'test-token',
        ]);

        // Will fail validation, but should NOT be 429
        $this->assertNotSame(429, $response->status());
    }

    public function test_hardware_attendance_returns_429_when_limit_exceeded(): void
    {
        $route = route('api.attendance.mark');

        // Send 60 requests (the limit)
        for ($i = 0; $i < 60; $i++) {
            $this->postJson($route, [
                'nfc_card_id' => 'test-card',
                'device_token' => 'test-token',
            ]);
        }

        // 61st request should be throttled
        $response = $this->postJson($route, [
            'nfc_card_id' => 'test-card',
            'device_token' => 'test-token',
        ]);

        $response->assertStatus(429);
    }

    // --- Missing key returns 401, not 429 ---

    public function test_missing_api_key_returns_401_not_rate_limit(): void
    {
        $response = $this->getJson(route('api.stats.index', ['schoolSlug' => 'rate-test']));

        $response->assertStatus(401);
    }

    // --- Rate limit headers ---

    public function test_api_response_includes_rate_limit_headers(): void
    {
        $rawKey = Str::random(40);
        SchoolApiKey::forceCreate([
            'school_id' => $this->school->id,
            'name' => 'Header Test',
            'key_hash' => hash('sha256', $rawKey),
            'permissions' => ['attendance'],
            'created_by' => $this->admin->id,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$rawKey}"])
            ->getJson(route('api.stats.index', ['schoolSlug' => 'rate-test']));

        $response->assertOk();
        // Laravel throttle middleware sets these headers
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }
}
