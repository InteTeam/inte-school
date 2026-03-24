<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LegalDocumentTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LegalDocumentTemplate>
 */
class LegalDocumentTemplateFactory extends Factory
{
    protected $model = LegalDocumentTemplate::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['privacy_policy', 'terms_conditions']),
            'name' => 'UK School '.fake()->words(3, true).' Template',
            'content' => '<p>This is a template document for '.fake()->company().'.</p>',
            'is_active' => true,
        ];
    }

    public function privacyPolicy(): static
    {
        return $this->state([
            'type' => 'privacy_policy',
            'name' => 'UK School Privacy Policy Template v1',
        ]);
    }

    public function termsConditions(): static
    {
        return $this->state([
            'type' => 'terms_conditions',
            'name' => 'UK School Terms & Conditions Template v1',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
