<?php

namespace Database\Factories;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Staircase;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'staircase_number' => fn () => Staircase::query()->where('has_sauna', true)->value('number')
                ?? Staircase::factory()->withSauna()->create()->number,
            'date' => CarbonImmutable::today()->toDateString(),
            'is_weekend' => false,
            'status' => ReservationStatus::Reserved,
            'coins_collected' => null,
            'note' => null,
        ];
    }

    public function on(string $date): static
    {
        $day = CarbonImmutable::parse($date);

        return $this->state(fn () => ['date' => $day->toDateString(), 'is_weekend' => $day->isSaturday()]);
    }

    public function status(ReservationStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
