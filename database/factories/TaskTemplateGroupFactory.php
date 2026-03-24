<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use App\Models\TaskTemplateGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskTemplateGroup>
 */
class TaskTemplateGroupFactory extends Factory
{
    protected $model = TaskTemplateGroup::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => $this->faker->words(3, true) . ' Checklist',
            'department_label' => null,
            'task_type' => 'staff',
        ];
    }
}
