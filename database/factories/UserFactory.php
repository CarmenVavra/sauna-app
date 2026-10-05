<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Staircase;
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
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'username' => fake()->unique()->userName().Str::lower(Str::random(4)),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::User,
            'staircase_number' => fn () => Staircase::query()->value('number') ?? Staircase::factory()->create()->number,
            'apartment' => 1,
            'must_change_password' => false,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'first_name' => 'Hauswart',
            'last_name' => '',
            'username' => 'hauswart',
            'role' => Role::Admin,
            'staircase_number' => null,
            'apartment' => null,
        ]);
    }

    public function mustChangePassword(): static
    {
        return $this->state(fn () => ['must_change_password' => true]);
    }
}
