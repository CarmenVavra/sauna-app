<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Ein reservierbarer Block: ein Werktag oder ein Wochenende (Sa + So),
 * gespeichert mit dem Samstag als Datum.
 */
final class Block
{
    public function __construct(
        public readonly CarbonImmutable $date,
        public readonly bool $weekend,
    ) {}

    /** Ordnet einen Kalendertag seinem Block zu (Sonntag gehört zum Samstag davor). */
    public static function fromDay(CarbonInterface $day): self
    {
        $day = CarbonImmutable::instance($day)->startOfDay();

        return match ($day->dayOfWeek) {
            CarbonInterface::SATURDAY => new self($day, true),
            CarbonInterface::SUNDAY => new self($day->subDay(), true),
            default => new self($day, false),
        };
    }

    public function end(): CarbonImmutable
    {
        return $this->weekend ? $this->date->addDay() : $this->date;
    }

    /** Ist der Block nicht mehr reservierbar? Wochenenden nur, solange der Freitag nicht vorbei ist. */
    public function isPast(CarbonInterface $today): bool
    {
        $today = CarbonImmutable::instance($today)->startOfDay();

        return $this->weekend ? $this->date->subDay()->lt($today) : $this->date->lt($today);
    }

    /** „Do 1. Okt“ bzw. „Sa 3. – So 4. Okt“ */
    public function when(): string
    {
        if (! $this->weekend) {
            return Fmt::day($this->date);
        }
        $a = $this->date;
        $b = $a->addDay();
        $monthA = $a->month !== $b->month ? ' '.Fmt::MONTHS_SHORT[$a->month - 1] : '';

        return 'Sa '.$a->day.'.'.$monthA.' – So '.$b->day.'. '.Fmt::MONTHS_SHORT[$b->month - 1];
    }

    /** „Donnerstag, 1. Oktober“ bzw. „Samstag, 3. – Sonntag, 4. Oktober“ */
    public function whenLong(): string
    {
        $a = $this->date;
        if (! $this->weekend) {
            return Fmt::WEEKDAYS_LONG[$a->dayOfWeek].', '.$a->day.'. '.Fmt::MONTHS[$a->month - 1];
        }
        $b = $a->addDay();
        $monthA = $a->month !== $b->month ? ' '.Fmt::MONTHS[$a->month - 1] : '';

        return 'Samstag, '.$a->day.'.'.$monthA.' – Sonntag, '.$b->day.'. '.Fmt::MONTHS[$b->month - 1];
    }

    /** Abholfrist, z. B. „bis Fr 2. Okt, 12:00“. */
    public function pickup(Setting $settings): string
    {
        if ($this->weekend) {
            return 'bis '.Fmt::day($this->date->subDay()).', '.$settings->fri_until;
        }
        $until = $this->date->dayOfWeek === CarbonInterface::FRIDAY ? $settings->fri_until : $settings->mon_thu_until;

        return 'bis '.Fmt::day($this->date).', '.$until;
    }

    /** Rückgabe, z. B. „So 4. Okt abends, Postkasten“. */
    public function returnInfo(): string
    {
        return Fmt::day($this->end()).' abends, Postkasten';
    }
}
