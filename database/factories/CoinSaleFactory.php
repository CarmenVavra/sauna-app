<?php

namespace Database\Factories;

use App\Models\CoinSale;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoinSale>
 */
class CoinSaleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'sold_on' => CarbonImmutable::today()->toDateString(),
            'quantity' => 10,
            'unit_price' => 5,
        ];
    }
}
