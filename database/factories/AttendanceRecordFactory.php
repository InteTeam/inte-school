<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\AttendanceRegister;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'register_id' => AttendanceRegister::factory(),
            'student_id' => User::factory(),
            'status' => 'present',
            'marked_by' => User::factory(),
            'marked_via' => 'manual',
            'pre_notified' => false,
            'notes' => null,
        ];
    }
}
