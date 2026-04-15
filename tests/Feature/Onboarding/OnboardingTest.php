<?php

declare(strict_types=1);

namespace Tests\Feature\Onboarding;

use App\Models\LegalDocumentTemplate;
use App\Models\School;
use App\Models\SchoolLegalDocument;
use App\Models\User;
use App\Services\OnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_is_created_with_minimum_fields(): void
    {
        $admin = User::factory()->rootAdmin()->create();
        $service = app(OnboardingService::class);

        $school = $service->createSchoolWithAdmin([
            'name' => 'Test Academy',
            'slug' => 'test-academy',
        ], $admin);

        $this->assertDatabaseHas('schools', [
            'name' => 'Test Academy',
            'slug' => 'test-academy',
        ]);
        $this->assertTrue($school->fresh()->isActive());
    }

    public function test_legal_docs_are_pre_filled_from_active_templates(): void
    {
        LegalDocumentTemplate::factory()->privacyPolicy()->create();
        LegalDocumentTemplate::factory()->termsConditions()->create();

        $admin = User::factory()->rootAdmin()->create();
        $service = app(OnboardingService::class);

        $school = $service->createSchoolWithAdmin([
            'name' => 'Test School',
            'slug' => 'test-school',
        ], $admin);

        $this->assertDatabaseHas('school_legal_documents', [
            'school_id' => $school->id,
            'type' => 'privacy_policy',
            'is_published' => false,
        ]);
        $this->assertDatabaseHas('school_legal_documents', [
            'school_id' => $school->id,
            'type' => 'terms_conditions',
            'is_published' => false,
        ]);
    }

    public function test_inactive_templates_are_not_used_for_pre_fill(): void
    {
        LegalDocumentTemplate::factory()->privacyPolicy()->inactive()->create();
        LegalDocumentTemplate::factory()->termsConditions()->create();

        $admin = User::factory()->rootAdmin()->create();
        $service = app(OnboardingService::class);

        $school = $service->createSchoolWithAdmin([
            'name' => 'Test School',
            'slug' => 'test-school',
        ], $admin);

        $docs = SchoolLegalDocument::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->get();

        $this->assertCount(1, $docs);
        $this->assertSame('terms_conditions', $docs->first()->type);
    }

    public function test_slug_uniqueness_is_validated(): void
    {
        School::factory()->create(['slug' => 'existing-slug']);

        $service = app(OnboardingService::class);

        $this->assertFalse($service->isSlugAvailable('existing-slug'));
        $this->assertTrue($service->isSlugAvailable('new-slug'));
    }

    public function test_school_cannot_go_live_without_published_legal_docs(): void
    {
        $admin = User::factory()->rootAdmin()->create();
        $service = app(OnboardingService::class);

        LegalDocumentTemplate::factory()->privacyPolicy()->create();
        LegalDocumentTemplate::factory()->termsConditions()->create();

        $school = $service->createSchoolWithAdmin([
            'name' => 'Draft School',
            'slug' => 'draft-school',
        ], $admin);

        $this->assertFalse($service->schoolCanGoLive($school));
    }

    public function test_school_can_go_live_when_both_docs_are_published(): void
    {
        $admin = User::factory()->rootAdmin()->create();
        $school = School::factory()->create();

        SchoolLegalDocument::forceCreate([
            'school_id' => $school->id,
            'type' => 'privacy_policy',
            'content' => '<p>Privacy</p>',
            'version' => '1.0',
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $admin->id,
            'created_by' => $admin->id,
        ]);
        SchoolLegalDocument::forceCreate([
            'school_id' => $school->id,
            'type' => 'terms_conditions',
            'content' => '<p>Terms</p>',
            'version' => '1.0',
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $admin->id,
            'created_by' => $admin->id,
        ]);

        $service = app(OnboardingService::class);

        $this->assertTrue($service->schoolCanGoLive($school));
    }

    public function test_onboarding_step1_validates_slug_uniqueness(): void
    {
        School::factory()->create(['slug' => 'taken-slug']);
        $admin = User::factory()->rootAdmin()->create();

        $response = $this->actingAs($admin)
            ->post('/onboarding/step-1', [
                'name' => 'My School',
                'slug' => 'taken-slug',
            ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_onboarding_step1_stores_data_in_session(): void
    {
        $admin = User::factory()->rootAdmin()->create();

        $response = $this->actingAs($admin)
            ->post('/onboarding/step-1', [
                'name' => 'My School',
                'slug' => 'my-school',
            ]);

        $response->assertRedirect('/onboarding/step-2');
        $this->assertSame('My School', session('onboarding.step1.name'));
    }

    // --- SOP: Guest redirect ---

    public function test_guest_cannot_access_onboarding(): void
    {
        $this->get('/onboarding/step-1')->assertRedirect('/login');
    }

    public function test_guest_cannot_post_onboarding(): void
    {
        $this->post('/onboarding/step-1', [])->assertRedirect('/login');
    }
}
