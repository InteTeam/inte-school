<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SmsLog> */
final class SmsLogFactory extends Factory
{
    protected $model = SmsLog::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'recipient_id' => User::factory(),
            'phone_number' => '+447' . $this->faker->numerify('#########'),
            'status' => 'queued',
            'segments' => 1,
            'cost_pence' => 0,
            'sent_at' => now(),
        ];
    }

    public function delivered(): static
    {
        return $this->state(fn () => [
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function failed(string $reason = 'Invalid phone number'): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }
}
