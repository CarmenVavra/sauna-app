<?php

namespace Database\Factories;

use App\Models\Oil;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Oil>
 */
class OilFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->word(),
            'brand' => null,
            'note' => null,
            'effect' => null,
        ];
    }
}
