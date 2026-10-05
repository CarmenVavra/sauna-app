<?php

namespace Database\Factories;

use App\Models\Blend;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Blend>
 */
class BlendFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(2, true),
            'description' => null,
        ];
    }

    /**
     * @param  list<array{0: string, 1: int, 2?: string|null}>  $ingredients  [Öl, Tropfen, Notiz]
     */
    public function withIngredients(array $ingredients): static
    {
        return $this->afterCreating(function (Blend $blend) use ($ingredients) {
            foreach ($ingredients as $k => $i) {
                $blend->ingredients()->create(['position' => $k + 1, 'oil_name' => $i[0], 'drops' => $i[1], 'note' => $i[2] ?? null]);
            }
        });
    }
}
