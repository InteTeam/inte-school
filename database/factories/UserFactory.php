<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('Password1!'),
            'phone' => null,
            'whatsapp_number' => null,
            'is_root_admin' => false,
            'disabled_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function rootAdmin(): static
    {
        return $this->state(fn () => ['is_root_admin' => true]);
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['disabled_at' => now()]);
    }

    public function withTwoFactor(): static
    {
        return $this->state(fn () => ['two_factor_secret' => 'JBSWY3DPEHPK3PXP']);
    }
}
