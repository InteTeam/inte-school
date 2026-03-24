<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use App\Models\Task;
use App\Models\TaskItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskItem>
 */
class TaskItemFactory extends Factory
{
    protected $model = TaskItem::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'task_id' => Task::factory(),
            'title' => $this->faker->sentence(4),
            'is_completed' => false,
            'is_custom' => true,
            'sort_order' => 0,
        ];
    }
}
