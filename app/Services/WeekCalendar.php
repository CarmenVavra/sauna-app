<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Setting;
use App\Models\Staircase;
use App\Support\Block;
use App\Support\Fmt;
use Carbon\CarbonImmutable;

/**
 * Wochenansicht des Hauswarts: je Sauna Mo–Fr plus Wochenende als Block.
 */
class WeekCalendar
{
    /**
     * @return array{kw:string, title:string, start:CarbonImmutable, prev:string, next:string, current:string, head:list<array{label:string,today:bool}>, rows:list<array{sauna:string, number:int, cells:list<array<string,mixed>>}>, reservations:list<Reservation>}
     */
    public function build(CarbonImmutable $weekStart, CarbonImmutable $today, Setting $settings): array
    {
        $monday = $weekStart->startOfWeek(CarbonImmutable::MONDAY)->startOfDay();
        $blocks = [];
        for ($i = 0; $i < 5; $i++) {
            $blocks[] = new Block($monday->addDays($i), false);
        }
        $blocks[] = new Block($monday->addDays(5), true);

        $reservations = Reservation::query()->active()->with('user')
            ->whereBetween('date', [$monday->toDateString(), $monday->addDays(5)->toDateString()])
            ->get()
            ->keyBy(fn (Reservation $r) => $r->staircase_number.'|'.$r->date->toDateString());

        $head = [];
        foreach (array_slice($blocks, 0, 5) as $b) {
            $isToday = $b->date->isSameDay($today);
            $head[] = ['label' => Fmt::day($b->date).($isToday ? ' · heute' : ''), 'today' => $isToday];
        }
        $head[] = ['label' => 'Wochenende '.preg_replace('/^Sa /', '', $blocks[5]->when()), 'today' => false];

        $rows = [];
        foreach (Staircase::query()->withSauna()->get() as $sauna) {
            $cells = [];
            foreach ($blocks as $b) {
                $r = $reservations->get($sauna->number.'|'.$b->date->toDateString());
                if ($r) {
                    $cells[] = ['type' => 'reservation', 'reservation' => $r];
                } elseif ($b->isPast($today)) {
                    $cells[] = ['type' => 'past'];
                } else {
                    $cells[] = [
                        'type' => 'free',
                        'sauna' => $sauna->number,
                        'date' => $b->date->toDateString(),
                        'when' => $b->when(),
                        'pick' => $b->pickup($settings),
                    ];
                }
            }
            $rows[] = ['sauna' => 'Stiege '.$sauna->number, 'number' => $sauna->number, 'cells' => $cells];
        }

        $sunday = $monday->addDays(6);

        return [
            'kw' => 'KW '.$monday->isoWeek(),
            'title' => Fmt::day($monday).' – '.Fmt::day($sunday).' '.$sunday->year,
            'start' => $monday,
            'prev' => $monday->subWeek()->toDateString(),
            'next' => $monday->addWeek()->toDateString(),
            'current' => $today->startOfWeek(CarbonImmutable::MONDAY)->toDateString(),
            'head' => $head,
            'rows' => $rows,
            'reservations' => $reservations->values()->all(),
        ];
    }
}
