<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LegalDocumentTemplate;
use App\Models\School;
use App\Models\SchoolLegalDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;

final class OnboardingService
{
    public function __construct(
        private readonly SchoolService $schoolService,
    ) {}

    /** @param array<string, mixed> $data */
    public function createSchoolWithAdmin(array $data, User $admin, ?UploadedFile $logo = null): School
    {
        $school = $this->schoolService->create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'plan' => $data['plan'] ?? 'standard',
        ]);

        if ($logo !== null) {
            $this->schoolService->uploadLogo($school, $logo);
        }

        $this->preFillLegalDocuments($school, $admin);

        return $school;
    }

    private function preFillLegalDocuments(School $school, User $createdBy): void
    {
        $templates = LegalDocumentTemplate::where('is_active', true)->get();

        foreach ($templates as $template) {
            SchoolLegalDocument::create([
                'school_id' => $school->id,
                'type' => $template->type,
                'content' => $template->content,
                'version' => '1.0',
                'is_published' => false,
                'created_by' => $createdBy->id,
            ]);
        }
    }

    public function schoolCanGoLive(School $school): bool
    {
        $requiredTypes = ['privacy_policy', 'terms_conditions'];

        foreach ($requiredTypes as $type) {
            $published = SchoolLegalDocument::forSchool($school->id)
                ->where('type', $type)
                ->where('is_published', true)
                ->exists();

            if (! $published) {
                return false;
            }
        }

        return true;
    }

    public function isSlugAvailable(string $slug, ?string $excludeId = null): bool
    {
        $query = School::where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return ! $query->exists();
    }
}
