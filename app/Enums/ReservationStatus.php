<?php

namespace App\Enums;

/**
 * Schlüssel-Status einer Reservierung.
 *
 * reserviert → ausgegeben → gemeldet → zurueck, reserviert → abgesagt.
 */
enum ReservationStatus: string
{
    case Reserved = 'reserviert';
    case Issued = 'ausgegeben';
    case Reported = 'gemeldet';
    case Returned = 'zurueck';
    case Cancelled = 'abgesagt';

    public function label(): string
    {
        return match ($this) {
            self::Reserved => 'Schlüssel abzuholen',
            self::Issued => 'Schlüssel ausgegeben',
            self::Reported => 'Eingeworfen gemeldet',
            self::Returned => 'Schlüssel zurück',
            self::Cancelled => 'Abgesagt',
        };
    }

    /** Erlaubte Übergänge laut Schlüssel-Ablauf. */
    public function canTransitionTo(self $to): bool
    {
        return in_array($to, match ($this) {
            self::Reserved => [self::Issued, self::Cancelled],
            self::Issued => [self::Reported, self::Returned],
            self::Reported => [self::Returned, self::Issued],
            self::Returned, self::Cancelled => [],
        }, true);
    }

    public function tagClass(): string
    {
        return match ($this) {
            self::Reserved => 'tag-outline',
            self::Issued => 'tag-accent',
            self::Reported => 'tag-accent-2',
            self::Returned, self::Cancelled => 'tag-neutral',
        };
    }
}
