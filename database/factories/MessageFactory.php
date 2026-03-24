<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Message;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        $school = School::factory()->create();
        $sender = User::factory()->create();

        return [
            'school_id' => $school->id,
            'sender_id' => $sender->id,
            'thread_id' => null,
            'transaction_id' => (string) Str::ulid(),
            'type' => 'announcement',
            'body' => fake()->paragraph(),
            'requires_read_receipt' => false,
            'sent_at' => now(),
        ];
    }

    public function attendanceAlert(): static
    {
        return $this->state([
            'type' => 'attendance_alert',
            'requires_read_receipt' => true,
        ]);
    }

    public function tripPermission(): static
    {
        return $this->state([
            'type' => 'trip_permission',
            'requires_read_receipt' => true,
        ]);
    }
}
