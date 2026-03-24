<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    protected $model = SchoolClass::class;

    public function definition(): array
    {
        $school = School::factory()->create();

        return [
            'school_id' => $school->id,
            'name' => fake()->randomElement(['1A', '2B', '3C', 'P1', 'P2', 'Year 4']),
            'year_group' => fake()->randomElement(['Year 1', 'Year 2', 'Year 3', 'Year 4', 'Year 5', 'Year 6']),
            'teacher_id' => null,
        ];
    }

    public function withTeacher(string $teacherId): static
    {
        return $this->state(['teacher_id' => $teacherId]);
    }
}
