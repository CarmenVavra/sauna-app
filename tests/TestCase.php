<?php

namespace Tests;

use App\Models\Staircase;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** „Heute“ wie im Prototyp: Donnerstag, 1. Oktober 2026. */
    protected const TODAY = '2026-10-01 10:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse(self::TODAY));
    }

    protected function sauna(int $number = 5): Staircase
    {
        return Staircase::query()->find($number)
            ?? Staircase::factory()->withSauna()->create(['number' => $number, 'apartments' => 12]);
    }

    protected function staircase(int $number = 3, int $apartments = 10): Staircase
    {
        return Staircase::query()->find($number)
            ?? Staircase::factory()->create(['number' => $number, 'apartments' => $apartments]);
    }

    protected function admin(): User
    {
        return User::query()->where('username', 'hauswart')->first() ?? User::factory()->admin()->create();
    }

    /** @param array<string, mixed> $attributes */
    protected function party(array $attributes = []): User
    {
        return User::factory()->create($attributes + [
            'staircase_number' => $this->staircase()->number,
            'apartment' => 1,
        ]);
    }
}
