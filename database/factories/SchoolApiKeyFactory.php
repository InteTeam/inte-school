<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use App\Models\SchoolApiKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SchoolApiKey>
 */
class SchoolApiKeyFactory extends Factory
{
    protected $model = SchoolApiKey::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => $this->faker->words(2, true) . ' Key',
            'key_hash' => hash('sha256', Str::random(40)),
            'permissions' => ['attendance'],
            'created_by' => User::factory(),
            'last_used_at' => null,
            'expires_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    public function withAllPermissions(): static
    {
        return $this->state(['permissions' => ['attendance', 'messages', 'homework', 'users']]);
    }
}
