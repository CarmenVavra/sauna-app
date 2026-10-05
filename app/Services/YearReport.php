<?php

namespace App\Services;

use App\Enums\Dosage;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use App\Support\Fmt;
use Carbon\CarbonImmutable;

/**
 * Jahresbilanz einer Partei. Kosten = verbrauchte Münzen × Ø Kaufpreis.
 */
class YearReport
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user, CarbonImmutable $today, Setting $settings, float $avgPrice): array
    {
        $year = $today->year;
        $sessions = $user->reservations()->active()->endedBefore($today)
            ->whereYear('date', $year)
            ->with('diaryEntry.oils')
            ->orderBy('date')
            ->get();

        $entries = $sessions->map(fn (Reservation $r) => $r->diaryEntry)->filter()->values();
        $coinsUsed = (int) $entries->sum('coins');
        $temps = $entries->pluck('temperature');
        $infusions = $entries->where('infusion', true);

        $oilAgg = [];
        foreach ($infusions as $e) {
            foreach ($e->oils as $o) {
                $oilAgg[$o->name] ??= ['drops' => 0, 'times' => 0];
                $oilAgg[$o->name]['drops'] += $o->drops;
                $oilAgg[$o->name]['times']++;
            }
        }
        uasort($oilAgg, fn ($a, $b) => $b['drops'] <=> $a['drops']);
        $maxDrops = max([1, ...array_column($oilAgg, 'drops')]);
        $topOil = array_key_first($oilAgg);

        $monthly = array_fill(0, 12, ['n' => 0, 'cost' => 0.0]);
        foreach ($sessions as $r) {
            $k = $r->date->month - 1;
            $monthly[$k]['n']++;
            if ($r->diaryEntry) {
                $monthly[$k]['cost'] += $r->diaryEntry->coins * $avgPrice;
            }
        }
        $maxN = max([1, ...array_column($monthly, 'n')]);

        $favourite = $sessions->countBy('staircase_number')->sortDesc()->keys()->first();
        $count = $sessions->count();
        $good = $infusions->filter(fn ($e) => $e->dosage === Dosage::Right)->count();
        $weekends = $sessions->where('is_weekend', true)->count();

        return [
            'year' => $year,
            'until' => Fmt::day($today),
            'count' => $count,
            'cost' => Fmt::eur($coinsUsed * $avgPrice),
            'coins' => $coinsUsed,
            'hours' => (int) round($coinsUsed * $settings->minutes_per_coin / 60),
            'avgTemp' => $temps->isNotEmpty() ? round($temps->avg()).' °C' : '—',
            'maxTemp' => $temps->isNotEmpty() ? $temps->max().' °C' : '—',
            'infusions' => $infusions->count(),
            'favourite' => $favourite ? 'Stiege '.$favourite : '—',
            'summary' => 'Im Schnitt '.Fmt::eur($count ? $coinsUsed * $avgPrice / $count : 0).' pro Saunagang. '
                .$weekends.' davon an Wochenenden'.($topOil ? ', '.$topOil.' war das meistverwendete Öl.' : '.'),
            'months' => array_map(fn ($m, $i) => [
                'label' => Fmt::MONTHS_SHORT[$i],
                'name' => Fmt::MONTHS[$i],
                'n' => $m['n'],
                'height' => $m['n'] ? (int) round($m['n'] / $maxN * 150) : 2,
                'cost' => $m['cost'] ? str_replace(',00', '', Fmt::eur($m['cost'])) : '–',
            ], $monthly, array_keys($monthly)),
            'oils' => array_map(fn ($name, $o) => [
                'name' => $name,
                'label' => $o['drops'].' Tropfen · '.$o['times'].'×',
                'width' => (int) round($o['drops'] / $maxDrops * 100),
            ], array_keys($oilAgg), $oilAgg),
            'doseLine' => $infusions->count()
                ? 'Bei '.$good.' von '.$infusions->count().' Aufgüssen passte die Dosierung.'
                : 'Noch keine Aufgüsse.',
        ];
    }
}
