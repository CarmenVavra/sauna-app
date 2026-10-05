<?php

namespace Database\Factories;

use App\Models\Staircase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staircase>
 */
class StaircaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => fn () => ((int) Staircase::query()->max('number')) + 1,
            'apartments' => 10,
            'has_sauna' => false,
        ];
    }

    public function withSauna(): static
    {
        return $this->state(fn () => ['has_sauna' => true]);
    }
}
