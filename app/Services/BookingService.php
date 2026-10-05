<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\Staircase;
use App\Models\User;
use App\Support\Block;
use App\Support\Fmt;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reservierungsregeln: ganzer Tag, eine Partei pro Sauna und Tag,
 * Wochenende als Block, keine zwei Reservierungen einer Partei am selben Tag.
 */
class BookingService
{
    /** Wie viele Monate (inkl. aktuellem) im Reservierungskalender angezeigt werden. */
    public const MONTHS_AHEAD = 3;

    /** Prüft einen Block und liefert die Fehlermeldung oder null, wenn er frei ist. */
    public function blockError(int $sauna, Block $block, User $party, ?int $ignoreId, CarbonInterface $today, bool $forAdmin = false): ?string
    {
        if (! Staircase::query()->whereKey($sauna)->where('has_sauna', true)->exists()) {
            return 'In dieser Stiege gibt es keine Sauna.';
        }

        if ($block->isPast($today)) {
            return $block->weekend
                ? 'Für dieses Wochenende kann der Schlüssel nicht mehr bis Freitag abgeholt werden.'
                : 'Dieser Tag liegt in der Vergangenheit.';
        }

        $taken = Reservation::query()->active()
            ->where('staircase_number', $sauna)
            ->where('date', $block->date->toDateString())
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
        if ($taken) {
            return 'Die Sauna ist an diesem Tag bereits belegt.';
        }

        $ownSameDay = Reservation::query()->active()
            ->where('user_id', $party->id)
            ->where('date', $block->date->toDateString())
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
        if ($ownSameDay) {
            return $forAdmin
                ? 'Diese Partei hat an diesem Tag bereits eine Reservierung.'
                : 'Sie haben an diesem Tag bereits eine Reservierung.';
        }

        return null;
    }

    /**
     * Legt eine Reservierung an oder verschiebt eine bestehende.
     *
     * @throws ValidationException
     */
    public function book(User $party, int $sauna, CarbonInterface $day, ?string $note, CarbonInterface $today, ?Reservation $existing = null, bool $forAdmin = false): Reservation
    {
        $block = Block::fromDay($day);

        return DB::transaction(function () use ($party, $sauna, $block, $note, $today, $existing, $forAdmin) {
            $error = $this->blockError($sauna, $block, $party, $existing?->id, $today, $forAdmin);
            if ($error !== null) {
                throw ValidationException::withMessages(['date' => $error]);
            }

            $attributes = [
                'staircase_number' => $sauna,
                'date' => $block->date,
                'is_weekend' => $block->weekend,
                'note' => $note !== null && trim($note) !== '' ? trim($note) : null,
            ];

            if ($existing) {
                $existing->update($attributes);

                return $existing;
            }

            return Reservation::query()->create($attributes + [
                'user_id' => $party->id,
                'status' => ReservationStatus::Reserved,
            ]);
        });
    }

    /**
     * Daten für den Reservierungskalender einer Partei (Saunen × Tage).
     *
     * @return array{saunas: list<array{number:int,label:string,nextFree:string}>, months: list<array<string,mixed>>, blocks: array<string, array<string,string>>}
     */
    public function availability(User $party, ?Reservation $editing, CarbonImmutable $today, Setting $settings): array
    {
        $saunas = Staircase::query()->withSauna()->pluck('number')->map(fn ($n) => (int) $n)->all();
        $first = $today->startOfMonth();
        $last = $first->addMonths(self::MONTHS_AHEAD)->subDay();
        $horizon = $last->max($today->addDays(91));

        $reservations = Reservation::query()->active()
            ->whereBetween('date', [$today->subDay()->toDateString(), $horizon->toDateString()])
            ->when($editing, fn ($q) => $q->whereKeyNot($editing->id))
            ->get(['id', 'user_id', 'staircase_number', 'date']);

        $taken = [];
        $mine = [];
        foreach ($reservations as $r) {
            $taken[$r->staircase_number.'|'.$r->date->toDateString()] = true;
            if ($r->user_id === $party->id) {
                $mine[$r->date->toDateString()] = true;
            }
        }

        $isFree = fn (int $sauna, Block $b): bool => ! $b->isPast($today)
            && ! isset($taken[$sauna.'|'.$b->date->toDateString()])
            && ! isset($mine[$b->date->toDateString()]);

        $saunaList = array_map(function (int $sauna) use ($today, $isFree) {
            $next = '—';
            for ($i = 0; $i < 90; $i++) {
                $day = $today->addDays($i);
                if ($day->dayOfWeek === CarbonInterface::SUNDAY) {
                    continue;
                }
                $block = Block::fromDay($day);
                if ($isFree($sauna, $block)) {
                    $next = $block->when();
                    break;
                }
            }

            return ['number' => $sauna, 'label' => 'Stiege '.$sauna, 'nextFree' => $next];
        }, $saunas);

        $months = [];
        $blocks = [];
        for ($m = $first; $m->lte($last); $m = $m->addMonth()) {
            $days = [];
            for ($d = $m; $d->month === $m->month; $d = $d->addDay()) {
                $block = Block::fromDay($d);
                $key = $block->date->toDateString();
                $blocks[$key] ??= [
                    'when' => $block->when(),
                    'whenLong' => $block->whenLong(),
                    'pick' => $block->pickup($settings),
                    'ret' => $block->returnInfo(),
                ];
                $takenBy = [];
                foreach ($saunas as $sauna) {
                    $takenBy[$sauna] = isset($taken[$sauna.'|'.$key]);
                }
                $days[] = [
                    'n' => $d->day,
                    'date' => $d->toDateString(),
                    'block' => $key,
                    'weekend' => $block->weekend,
                    'today' => $d->isSameDay($today),
                    'past' => $block->isPast($today),
                    'mine' => isset($mine[$key]),
                    'taken' => $takenBy,
                ];
            }
            $months[] = [
                'key' => $m->format('Y-m'),
                'title' => Fmt::month($m),
                'offset' => ($m->dayOfWeek + 6) % 7,
                'days' => $days,
            ];
        }

        return ['saunas' => $saunaList, 'months' => $months, 'blocks' => $blocks];
    }
}
