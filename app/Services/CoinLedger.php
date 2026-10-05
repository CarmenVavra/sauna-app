<?php

namespace App\Services;

use App\Models\CoinSale;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

/**
 * Münzbestand des Hauswarts. Münzen sind physisch und werden recycelt:
 * bei Parteien = verkauft − eingesammelt, im Lager = Gesamtbestand − bei Parteien.
 */
class CoinLedger
{
    /**
     * @return array{total:int, sold:int, used:int, circulating:int, stock:int, soldYear:int, usedYear:int, revenueYear:float}
     */
    public function summary(Setting $settings, int $year): array
    {
        $sold = (int) CoinSale::query()->sum('quantity');
        $used = (int) Reservation::withTrashed()->sum('coins_collected');
        $circulating = $sold - $used;

        $yearSales = CoinSale::query()->whereYear('sold_on', $year);

        return [
            'total' => $settings->coins_total,
            'sold' => $sold,
            'used' => $used,
            'circulating' => $circulating,
            'stock' => $settings->coins_total - $circulating,
            'soldYear' => (int) (clone $yearSales)->sum('quantity'),
            'usedYear' => (int) Reservation::withTrashed()->whereYear('date', $year)->sum('coins_collected'),
            'revenueYear' => (float) (clone $yearSales)->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as revenue')->value('revenue'),
        ];
    }

    /**
     * Übrige Münzen je Partei (gekauft − eingesammelt), absteigend sortiert.
     *
     * @return BaseCollection<int, array{user:User, bought:int, used:int, left:int}>
     */
    public function balances(int $limit = 10): BaseCollection
    {
        return User::withTrashed()
            ->whereHas('coinSales')
            ->withSum('coinSales as bought', 'quantity')
            ->withSum('reservations as used', 'coins_collected')
            ->get()
            ->map(fn (User $u) => [
                'user' => $u,
                'bought' => (int) $u->getAttribute('bought'),
                'used' => (int) $u->getAttribute('used'),
                'left' => (int) $u->getAttribute('bought') - (int) $u->getAttribute('used'),
            ])
            ->sortByDesc('left')
            ->take($limit)
            ->values();
    }

    /** @return Collection<int, CoinSale> */
    public function recentSales(int $limit = 10): Collection
    {
        return CoinSale::query()->with('user')->orderByDesc('sold_on')->orderByDesc('id')->limit($limit)->get();
    }
}
