<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Deutsche (österreichische) Datums- und Betragsformate wie im Prototyp.
 */
final class Fmt
{
    public const WEEKDAYS = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];

    public const WEEKDAYS_LONG = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];

    public const MONTHS = ['Jänner', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

    public const MONTHS_SHORT = ['Jän', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];

    public const NUMBER_WORDS = ['Kein', 'Ein', 'Zwei', 'Drei', 'Vier', 'Fünf', 'Sechs', 'Sieben', 'Acht', 'Neun', 'Zehn', 'Elf', 'Zwölf'];

    /** „Do 1. Okt“ */
    public static function day(CarbonInterface $d): string
    {
        return self::WEEKDAYS[$d->dayOfWeek].' '.$d->day.'. '.self::MONTHS_SHORT[$d->month - 1];
    }

    /** „1. Okt 2026“ */
    public static function dayYear(CarbonInterface $d): string
    {
        return $d->day.'. '.self::MONTHS_SHORT[$d->month - 1].' '.$d->year;
    }

    /** „Donnerstag, 1. Oktober 2026“ */
    public static function long(CarbonInterface $d): string
    {
        return self::WEEKDAYS_LONG[$d->dayOfWeek].', '.$d->day.'. '.self::MONTHS[$d->month - 1].' '.$d->year;
    }

    /** „Oktober 2026“ */
    public static function month(CarbonInterface $d): string
    {
        return self::MONTHS[$d->month - 1].' '.$d->year;
    }

    /** „5,00 €“ */
    public static function eur(float|int|string|null $amount): string
    {
        return number_format(round((float) $amount, 2), 2, ',', '.').' €';
    }

    /** „Sieben“ bis zwölf, danach die Ziffer. */
    public static function numberWord(int $n): string
    {
        return self::NUMBER_WORDS[$n] ?? (string) $n;
    }
}
