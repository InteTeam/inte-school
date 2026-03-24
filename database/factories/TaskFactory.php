<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'type' => 'staff_task',
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'status' => 'todo',
            'priority' => null,
            'assignee_id' => User::factory(),
            'assigned_by_id' => User::factory(),
            'due_at' => null,
        ];
    }
}
