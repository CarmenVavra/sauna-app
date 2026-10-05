<?php

namespace Database\Factories;

use App\Models\CoinPurchase;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoinPurchase>
 */
class CoinPurchaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'purchased_on' => CarbonImmutable::today()->toDateString(),
            'quantity' => 10,
            'unit_price' => 5,
        ];
    }
}
