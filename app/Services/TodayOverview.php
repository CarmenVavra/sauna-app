<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\Staircase;
use App\Support\Fmt;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Tagesübersicht des Hauswarts: Postkasten, Abholungen, Münzen, Schlüsselbrett.
 */
class TodayOverview
{
    /**
     * @return array{headline:string, mailbox:Collection<int,Reservation>, pickups:Collection<int,Reservation>, coins:Collection<int,Reservation>, keys:list<array{sauna:string,where:string,tag:string,class:string}>}
     */
    public function build(CarbonImmutable $today, Setting $settings): array
    {
        $mailbox = Reservation::query()->with('user')
            ->where(function (Builder $q) use ($today) {
                $q->where('status', ReservationStatus::Reported->value)
                    ->orWhere(fn (Builder $w) => $w->where('status', ReservationStatus::Issued->value)->endedBefore($today));
            })
            ->chronological()->get();

        $pickups = Reservation::query()->with('user')
            ->where('status', ReservationStatus::Reserved->value)
            ->whereBetween('date', [$today->toDateString(), $today->addDays(3)->toDateString()])
            ->chronological()->get();

        $coins = Reservation::query()->with('user')
            ->where('status', ReservationStatus::Returned->value)
            ->whereNull('coins_collected')
            ->where('date', '<', $today->toDateString())
            ->chronological()->get();

        return [
            'headline' => $this->headline($pickups->count(), $mailbox->count()),
            'mailbox' => $mailbox,
            'pickups' => $pickups,
            'coins' => $coins,
            'keys' => $this->keyBoard($today),
        ];
    }

    /** „Sieben Schlüssel warten auf Abholung. Im Postkasten sind zwei zu prüfen.“ */
    public function headline(int $pickups, int $mailbox): string
    {
        $first = Fmt::numberWord($pickups).' '.($pickups <= 1 ? 'Schlüssel wartet' : 'Schlüssel warten').' auf Abholung.';
        $second = match (true) {
            $mailbox === 0 => 'Im Postkasten ist nichts zu prüfen.',
            $mailbox === 1 => 'Im Postkasten ist einer zu prüfen.',
            default => 'Im Postkasten sind '.mb_strtolower(Fmt::numberWord($mailbox)).' zu prüfen.',
        };

        return $first.' '.$second;
    }

    /** @return list<array{sauna:string,where:string,tag:string,class:string}> */
    private function keyBoard(CarbonImmutable $today): array
    {
        $out = Reservation::query()->with('user')
            ->whereIn('status', [ReservationStatus::Issued->value, ReservationStatus::Reported->value])
            ->orderBy('date')
            ->get()
            ->groupBy('staircase_number');

        return Staircase::query()->withSauna()->get()->map(function (Staircase $s) use ($out, $today) {
            /** @var Reservation|null $r */
            $r = $out->get($s->number)?->first();
            $label = 'Stiege '.$s->number;

            if (! $r) {
                return ['sauna' => $label, 'where' => 'Beim Hauswart', 'tag' => 'am Brett', 'class' => 'tag-neutral'];
            }
            if ($r->status === ReservationStatus::Reported) {
                return ['sauna' => $label, 'where' => 'Im Postkasten (gemeldet)', 'tag' => 'prüfen', 'class' => 'tag-accent-2'];
            }
            $overdue = $r->isOverdue($today);

            return [
                'sauna' => $label,
                'where' => 'Bei '.$r->user->name.' seit '.Fmt::day($r->date),
                'tag' => $overdue ? 'überfällig' : 'ausgegeben',
                'class' => $overdue ? 'tag-accent-2' : 'tag-accent',
            ];
        })->values()->all();
    }
}
