<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\School;
use App\Models\SchoolApiKey;
use App\Models\SchoolLegalDocument;
use App\Models\Scopes\SchoolScope;
use App\Models\User;
use App\Models\UserLegalAcceptance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ApiKeyLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $rootAdmin;

    private User $admin;

    private SchoolLegalDocument $privacyDoc;

    private SchoolLegalDocument $termsDoc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootAdmin = User::factory()->rootAdmin()->create(['email' => 'root@example.com']);
        $this->school = School::factory()->create(['slug' => 'api-test-school']);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->school->users()->attach($this->admin->id, [
            'id' => Str::ulid(),
            'role' => 'admin',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);

        // Create legal docs once
        $this->privacyDoc = SchoolLegalDocument::forceCreate([
            'school_id' => $this->school->id,
            'type' => 'privacy_policy',
            'content' => '<p>Privacy</p>',
            'version' => '1.0',
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $this->rootAdmin->id,
            'created_by' => $this->rootAdmin->id,
        ]);
        $this->termsDoc = SchoolLegalDocument::forceCreate([
            'school_id' => $this->school->id,
            'type' => 'terms_conditions',
            'content' => '<p>Terms</p>',
            'version' => '1.0',
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $this->rootAdmin->id,
            'created_by' => $this->rootAdmin->id,
        ]);

        $this->acceptLegalDocs($this->admin);
    }

    private function acceptLegalDocs(User $user): void
    {
        foreach ([$this->privacyDoc, $this->termsDoc] as $doc) {
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

    private function actAsAdmin(): self
    {
        return $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id]);
    }

    // --- Creation ---

    public function test_admin_can_create_api_key_with_permissions(): void
    {
        $response = $this->actAsAdmin()
            ->post(route('admin.settings.api-keys.store'), [
                'name' => 'Council Integration',
                'permissions' => ['attendance', 'users'],
            ]);

        $response->assertRedirect(route('admin.settings.api-keys'));

        $key = SchoolApiKey::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $this->school->id)
            ->where('name', 'Council Integration')
            ->first();

        $this->assertNotNull($key);
        $this->assertSame(['attendance', 'users'], $key->permissions);
        $this->assertSame($this->admin->id, $key->created_by);
    }

    public function test_raw_key_is_flashed_once_after_creation(): void
    {
        $response = $this->actAsAdmin()
            ->post(route('admin.settings.api-keys.store'), [
                'name' => 'Flash Test Key',
                'permissions' => ['attendance'],
            ]);

        $response->assertSessionHas('generated_key');
        $rawKey = session('generated_key');

        $this->assertNotNull($rawKey);
        $this->assertSame(40, strlen($rawKey));
    }

    public function test_key_is_stored_as_sha256_hash(): void
    {
        $this->actAsAdmin()
            ->post(route('admin.settings.api-keys.store'), [
                'name' => 'Hash Test Key',
                'permissions' => ['attendance'],
            ]);

        $rawKey = session('generated_key');

        $key = SchoolApiKey::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $this->school->id)
            ->first();

        // key_hash is SHA-256 hex (64 chars), not the raw 40-char key
        $this->assertSame(64, strlen($key->key_hash));
        $this->assertSame(hash('sha256', $rawKey), $key->key_hash);
    }

    public function test_raw_key_is_never_stored_in_database(): void
    {
        $this->actAsAdmin()
            ->post(route('admin.settings.api-keys.store'), [
                'name' => 'No Plaintext Key',
                'permissions' => ['attendance'],
            ]);

        $rawKey = session('generated_key');

        // key_hash column must NOT contain the raw key
        $this->assertDatabaseMissing('school_api_keys', [
            'key_hash' => $rawKey,
        ]);
    }

    // --- Permission validation ---

    public function test_permissions_are_required(): void
    {
        $response = $this->actAsAdmin()
            ->post(route('admin.settings.api-keys.store'), [
                'name' => 'No Perms Key',
                'permissions' => [],
            ]);

        $response->assertSessionHasErrors('permissions');
    }

    public function test_invalid_permission_is_rejected(): void
    {
        $response = $this->actAsAdmin()
            ->post(route('admin.settings.api-keys.store'), [
                'name' => 'Bad Perm Key',
                'permissions' => ['attendance', 'admin_override'],
            ]);

        $response->assertSessionHasErrors('permissions.1');
    }

    public function test_name_is_required(): void
    {
        $response = $this->actAsAdmin()
            ->post(route('admin.settings.api-keys.store'), [
                'name' => '',
                'permissions' => ['attendance'],
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_name_max_length_is_100(): void
    {
        $response = $this->actAsAdmin()
            ->post(route('admin.settings.api-keys.store'), [
                'name' => str_repeat('a', 101),
                'permissions' => ['attendance'],
            ]);

        $response->assertSessionHasErrors('name');
    }

    // --- Expiry ---

    public function test_key_can_be_created_with_future_expiry(): void
    {
        $response = $this->actAsAdmin()
            ->post(route('admin.settings.api-keys.store'), [
                'name' => 'Expiring Key',
                'permissions' => ['attendance'],
                'expires_at' => now()->addYear()->toDateString(),
            ]);

        $response->assertRedirect(route('admin.settings.api-keys'));

        $key = SchoolApiKey::withoutGlobalScope(SchoolScope::class)
            ->where('name', 'Expiring Key')
            ->first();

        $this->assertNotNull($key->expires_at);
    }

    public function test_key_cannot_be_created_with_past_expiry(): void
    {
        $response = $this->actAsAdmin()
            ->post(route('admin.settings.api-keys.store'), [
                'name' => 'Past Expiry Key',
                'permissions' => ['attendance'],
                'expires_at' => now()->subDay()->toDateString(),
            ]);

        $response->assertSessionHasErrors('expires_at');
    }

    public function test_key_can_be_created_without_expiry(): void
    {
        $this->actAsAdmin()
            ->post(route('admin.settings.api-keys.store'), [
                'name' => 'No Expiry Key',
                'permissions' => ['attendance'],
            ]);

        $key = SchoolApiKey::withoutGlobalScope(SchoolScope::class)
            ->where('name', 'No Expiry Key')
            ->first();

        $this->assertNull($key->expires_at);
        $this->assertFalse($key->isExpired());
    }

    // --- Revocation ---

    public function test_admin_can_revoke_key(): void
    {
        $key = SchoolApiKey::forceCreate([
            'school_id' => $this->school->id,
            'name' => 'To Revoke',
            'key_hash' => hash('sha256', 'test-key'),
            'permissions' => ['attendance'],
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actAsAdmin()
            ->delete(route('admin.settings.api-keys.destroy', $key));

        $response->assertRedirect(route('admin.settings.api-keys'));
        $this->assertDatabaseMissing('school_api_keys', ['id' => $key->id]);
    }

    // --- Rotation (revoke old + create new) ---

    public function test_key_can_be_rotated_by_revoking_and_creating(): void
    {
        $oldKey = SchoolApiKey::forceCreate([
            'school_id' => $this->school->id,
            'name' => 'Old Key',
            'key_hash' => hash('sha256', 'old-key'),
            'permissions' => ['attendance'],
            'created_by' => $this->admin->id,
        ]);

        // Revoke old
        $this->actAsAdmin()
            ->delete(route('admin.settings.api-keys.destroy', $oldKey));

        // Create new
        $this->actAsAdmin()
            ->post(route('admin.settings.api-keys.store'), [
                'name' => 'New Key',
                'permissions' => ['attendance'],
            ]);

        $this->assertDatabaseMissing('school_api_keys', ['id' => $oldKey->id]);

        $newKey = SchoolApiKey::withoutGlobalScope(SchoolScope::class)
            ->where('name', 'New Key')
            ->first();

        $this->assertNotNull($newKey);
        $this->assertNotSame($oldKey->key_hash, $newKey->key_hash);
    }

    // --- API authentication ---

    public function test_valid_key_authenticates_api_request(): void
    {
        $rawKey = Str::random(40);
        SchoolApiKey::forceCreate([
            'school_id' => $this->school->id,
            'name' => 'API Test',
            'key_hash' => hash('sha256', $rawKey),
            'permissions' => ['attendance', 'users'],
            'created_by' => $this->admin->id,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$rawKey}"])
            ->getJson(route('api.stats.index', ['schoolSlug' => 'api-test-school']));

        $response->assertOk();
    }

    public function test_expired_key_is_rejected(): void
    {
        $rawKey = Str::random(40);
        SchoolApiKey::forceCreate([
            'school_id' => $this->school->id,
            'name' => 'Expired',
            'key_hash' => hash('sha256', $rawKey),
            'permissions' => ['attendance'],
            'created_by' => $this->admin->id,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$rawKey}"])
            ->getJson(route('api.stats.index', ['schoolSlug' => 'api-test-school']));

        $response->assertStatus(401);
    }

    public function test_last_used_at_is_updated_on_api_call(): void
    {
        $rawKey = Str::random(40);
        $key = SchoolApiKey::forceCreate([
            'school_id' => $this->school->id,
            'name' => 'Usage Tracking',
            'key_hash' => hash('sha256', $rawKey),
            'permissions' => ['attendance'],
            'created_by' => $this->admin->id,
        ]);

        $this->assertNull($key->last_used_at);

        $this->withHeaders(['Authorization' => "Bearer {$rawKey}"])
            ->getJson(route('api.stats.index', ['schoolSlug' => 'api-test-school']));

        $key->refresh();
        $this->assertNotNull($key->last_used_at);
    }

    // --- Cross-tenant isolation ---

    public function test_key_from_school_a_cannot_access_school_b_stats(): void
    {
        $otherSchool = School::factory()->create(['slug' => 'other-school']);
        $rawKey = Str::random(40);
        SchoolApiKey::forceCreate([
            'school_id' => $otherSchool->id,
            'name' => 'Other School Key',
            'key_hash' => hash('sha256', $rawKey),
            'permissions' => ['attendance'],
            'created_by' => $this->admin->id,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$rawKey}"])
            ->getJson(route('api.stats.index', ['schoolSlug' => 'api-test-school']));

        // School slug doesn't match key's school — should be 404
        $response->assertStatus(404);
    }

    public function test_api_keys_are_scoped_to_school_in_admin_list(): void
    {
        SchoolApiKey::forceCreate([
            'school_id' => $this->school->id,
            'name' => 'My School Key',
            'key_hash' => hash('sha256', 'key-1'),
            'permissions' => ['attendance'],
            'created_by' => $this->admin->id,
        ]);

        $otherSchool = School::factory()->create();
        SchoolApiKey::forceCreate([
            'school_id' => $otherSchool->id,
            'name' => 'Other School Key',
            'key_hash' => hash('sha256', 'key-2'),
            'permissions' => ['attendance'],
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actAsAdmin()
            ->get(route('admin.settings.api-keys'));

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Settings/ApiKeys')
            ->has('keys', 1) // only keys from this school
        );
    }

    // --- Role gates ---

    public function test_teacher_cannot_manage_api_keys(): void
    {
        $teacher = User::factory()->create(['email' => 'teacher@example.com']);
        $this->school->users()->attach($teacher->id, [
            'id' => Str::ulid(),
            'role' => 'teacher',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);
        $this->acceptLegalDocs($teacher);

        $this->withoutExceptionHandling();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->actingAs($teacher)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('admin.settings.api-keys.store'), [
                'name' => 'Unauthorized Key',
                'permissions' => ['attendance'],
            ]);
    }

    public function test_guest_cannot_create_api_key(): void
    {
        $response = $this->post(route('admin.settings.api-keys.store'), []);

        $response->assertRedirect('/login');
    }
}
