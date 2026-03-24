<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        $name = fake()->company().' School';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'custom_domain' => null,
            'logo_path' => null,
            'theme_config' => [],
            'settings' => [],
            'notification_settings' => [
                'sms_fallback_enabled' => false,
                'sms_timeout_seconds' => 900,
            ],
            'security_policy' => [],
            'plan' => 'standard',
            'rag_enabled' => false,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withRag(): static
    {
        return $this->state(['rag_enabled' => true]);
    }

    public function withFeature(string $feature): static
    {
        return $this->state(function (array $attributes) use ($feature) {
            $settings = $attributes['settings'] ?? [];
            $features = $settings['features'] ?? [];
            $features[$feature] = true;
            $settings['features'] = $features;

            return ['settings' => $settings];
        });
    }
}
