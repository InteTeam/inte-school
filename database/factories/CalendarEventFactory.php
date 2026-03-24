<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Calendar;
use App\Models\CalendarEvent;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('+1 day', '+1 month');

        return [
            'school_id' => School::factory(),
            'calendar_id' => Calendar::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'starts_at' => $start,
            'ends_at' => (clone $start)->modify('+1 hour'),
            'all_day' => false,
            'location' => $this->faker->optional()->address(),
            'meta' => null,
            'created_by' => User::factory(),
        ];
    }
}
