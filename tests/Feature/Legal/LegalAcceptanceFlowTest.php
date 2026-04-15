<?php

declare(strict_types=1);

namespace Tests\Feature\Legal;

use App\Models\School;
use App\Models\SchoolLegalDocument;
use App\Models\User;
use App\Models\UserLegalAcceptance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LegalAcceptanceFlowTest extends TestCase
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
        $this->school = School::factory()->create();

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->school->users()->attach($this->admin->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'admin',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);

        $this->privacyDoc = SchoolLegalDocument::forceCreate([
            'school_id' => $this->school->id,
            'type' => 'privacy_policy',
            'content' => '<p>Privacy Policy v1.0</p>',
            'version' => '1.0',
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $this->rootAdmin->id,
            'created_by' => $this->rootAdmin->id,
        ]);
        $this->termsDoc = SchoolLegalDocument::forceCreate([
            'school_id' => $this->school->id,
            'type' => 'terms_conditions',
            'content' => '<p>Terms v1.0</p>',
            'version' => '1.0',
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $this->rootAdmin->id,
            'created_by' => $this->rootAdmin->id,
        ]);
    }

    private function acceptAll(User $user): void
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

    // --- Middleware enforcement ---

    public function test_user_without_acceptance_is_redirected_to_legal_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->get('/admin/dashboard');

        $response->assertRedirect(route('legal.accept.show'));
    }

    public function test_user_with_acceptance_can_access_school_routes(): void
    {
        $this->acceptAll($this->admin);

        $response = $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->get('/admin/dashboard');

        $response->assertStatus(200);
    }

    // --- Root admin bypass ---

    public function test_root_admin_bypasses_legal_acceptance(): void
    {
        // Root admin with school context but no legal acceptance
        $this->school->users()->attach($this->rootAdmin->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'admin',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);

        $response = $this->actingAs($this->rootAdmin)
            ->withSession(['current_school_id' => $this->school->id])
            ->get('/admin/dashboard');

        $response->assertStatus(200);
    }

    // --- No redirect loop ---

    public function test_legal_acceptance_page_is_accessible_without_prior_acceptance(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->get(route('legal.accept.show'));

        // Must NOT redirect to itself (avoid loop)
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Legal/Accept'));
    }

    // --- Acceptance flow ---

    public function test_user_can_accept_all_documents(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('legal.accept.store'), [
                'document_ids' => [$this->privacyDoc->id, $this->termsDoc->id],
            ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('user_legal_acceptances', [
            'user_id' => $this->admin->id,
            'document_id' => $this->privacyDoc->id,
            'document_type' => 'privacy_policy',
            'document_version' => '1.0',
        ]);
        $this->assertDatabaseHas('user_legal_acceptances', [
            'user_id' => $this->admin->id,
            'document_id' => $this->termsDoc->id,
            'document_type' => 'terms_conditions',
        ]);
    }

    public function test_ip_address_is_recorded_on_acceptance(): void
    {
        $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('legal.accept.store'), [
                'document_ids' => [$this->privacyDoc->id],
            ]);

        $acceptance = UserLegalAcceptance::where('user_id', $this->admin->id)
            ->where('document_id', $this->privacyDoc->id)
            ->first();

        $this->assertNotNull($acceptance);
        $this->assertNotEmpty($acceptance->ip_address);
        $this->assertNotEmpty($acceptance->user_agent);
    }

    public function test_accepted_at_timestamp_is_set(): void
    {
        $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('legal.accept.store'), [
                'document_ids' => [$this->privacyDoc->id],
            ]);

        $acceptance = UserLegalAcceptance::where('user_id', $this->admin->id)
            ->where('document_id', $this->privacyDoc->id)
            ->first();

        $this->assertNotNull($acceptance->accepted_at);
    }

    // --- Version tracking ---

    public function test_new_version_requires_re_acceptance(): void
    {
        // Accept v1.0
        $this->acceptAll($this->admin);

        // Verify access works
        $response = $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->get('/admin/dashboard');
        $response->assertStatus(200);

        // Publish a new version of privacy policy
        $v2 = SchoolLegalDocument::forceCreate([
            'school_id' => $this->school->id,
            'type' => 'privacy_policy',
            'content' => '<p>Privacy Policy v2.0</p>',
            'version' => '2.0',
            'is_published' => true,
            'published_at' => now()->addMinute(),
            'published_by' => $this->rootAdmin->id,
            'created_by' => $this->rootAdmin->id,
        ]);

        // User should now be redirected again — v2.0 not accepted
        $response = $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->get('/admin/dashboard');

        $response->assertRedirect(route('legal.accept.show'));
    }

    public function test_accepting_new_version_restores_access(): void
    {
        // Accept v1.0
        $this->acceptAll($this->admin);

        // Publish v2.0
        $v2 = SchoolLegalDocument::forceCreate([
            'school_id' => $this->school->id,
            'type' => 'privacy_policy',
            'content' => '<p>Privacy Policy v2.0</p>',
            'version' => '2.0',
            'is_published' => true,
            'published_at' => now()->addMinute(),
            'published_by' => $this->rootAdmin->id,
            'created_by' => $this->rootAdmin->id,
        ]);

        // Accept v2.0
        $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('legal.accept.store'), [
                'document_ids' => [$v2->id],
            ]);

        // Access should now work
        $response = $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->get('/admin/dashboard');

        $response->assertStatus(200);
    }

    // --- Validation ---

    public function test_document_ids_are_required(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('legal.accept.store'), [
                'document_ids' => [],
            ]);

        $response->assertSessionHasErrors('document_ids');
    }

    public function test_document_ids_must_be_ulids(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('legal.accept.store'), [
                'document_ids' => ['not-a-ulid', 'also-not'],
            ]);

        $response->assertSessionHasErrors('document_ids.0');
    }

    // --- Multi-tenant ---

    public function test_acceptance_is_scoped_to_school(): void
    {
        // Accept for school A
        $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $this->school->id])
            ->post(route('legal.accept.store'), [
                'document_ids' => [$this->privacyDoc->id, $this->termsDoc->id],
            ]);

        // Create school B with its own legal docs
        $schoolB = School::factory()->create();
        $schoolB->users()->attach($this->admin->id, [
            'id' => \Illuminate\Support\Str::ulid(),
            'role' => 'admin',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);

        $schoolBDoc = SchoolLegalDocument::forceCreate([
            'school_id' => $schoolB->id,
            'type' => 'privacy_policy',
            'content' => '<p>School B Privacy</p>',
            'version' => '1.0',
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $this->rootAdmin->id,
            'created_by' => $this->rootAdmin->id,
        ]);

        // Admin accessing school B should be redirected — school B docs not accepted
        $response = $this->actingAs($this->admin)
            ->withSession(['current_school_id' => $schoolB->id])
            ->get('/admin/dashboard');

        $response->assertRedirect(route('legal.accept.show'));
    }

    // --- Guest cannot access ---

    public function test_guest_cannot_access_acceptance_page(): void
    {
        $response = $this->get(route('legal.accept.show'));

        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_post_acceptance(): void
    {
        $response = $this->post(route('legal.accept.store'), [
            'document_ids' => [$this->privacyDoc->id],
        ]);

        $response->assertRedirect('/login');
    }
}
