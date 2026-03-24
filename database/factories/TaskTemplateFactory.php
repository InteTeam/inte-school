<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use App\Models\TaskTemplate;
use App\Models\TaskTemplateGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskTemplate>
 */
class TaskTemplateFactory extends Factory
{
    protected $model = TaskTemplate::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'group_id' => TaskTemplateGroup::factory(),
            'name' => $this->faker->sentence(3),
            'sort_order' => 0,
            'default_deadline_hours' => $this->faker->optional()->numberBetween(1, 72),
        ];
    }
}
