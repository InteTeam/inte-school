<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FeatureRequest;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeatureRequest>
 */
class FeatureRequestFactory extends Factory
{
    protected $model = FeatureRequest::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'submitted_by' => User::factory(),
            'title' => $this->faker->sentence(5),
            'body' => $this->faker->paragraph(),
            'status' => 'open',
        ];
    }

    public function planned(): static
    {
        return $this->state(['status' => 'planned']);
    }
}
