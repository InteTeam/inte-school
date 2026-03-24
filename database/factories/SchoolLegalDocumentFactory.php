<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use App\Models\SchoolLegalDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolLegalDocument>
 */
class SchoolLegalDocumentFactory extends Factory
{
    protected $model = SchoolLegalDocument::class;

    public function definition(): array
    {
        $school = School::factory()->create();
        $user = User::factory()->create();

        return [
            'school_id' => $school->id,
            'type' => fake()->randomElement(['privacy_policy', 'terms_conditions']),
            'content' => '<p>This is the document content.</p>',
            'version' => '1.0',
            'is_published' => false,
            'published_at' => null,
            'published_by' => null,
            'created_by' => $user->id,
        ];
    }

    public function published(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_published' => true,
                'published_at' => now(),
                'published_by' => $attributes['created_by'],
            ];
        });
    }

    public function privacyPolicy(): static
    {
        return $this->state(['type' => 'privacy_policy']);
    }

    public function termsConditions(): static
    {
        return $this->state(['type' => 'terms_conditions']);
    }
}
