<?php

namespace Database\Factories;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Kandidat,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::Admin]);
    }

    public function umkm(): static
    {
        return $this->state(fn () => ['role' => UserRole::Umkm]);
    }

    public function kandidat(): static
    {
        return $this->state(fn () => ['role' => UserRole::Kandidat]);
    }
}
