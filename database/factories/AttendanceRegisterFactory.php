<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AttendanceRegister;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRegister>
 */
class AttendanceRegisterFactory extends Factory
{
    protected $model = AttendanceRegister::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'class_id' => SchoolClass::factory(),
            'teacher_id' => User::factory(),
            'register_date' => now()->toDateString(),
            'period' => null,
        ];
    }
}
