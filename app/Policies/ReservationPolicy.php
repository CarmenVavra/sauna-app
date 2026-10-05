<?php

namespace App\Policies;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Was eine Partei mit ihren eigenen Reservierungen tun darf.
 */
class ReservationPolicy
{
    /** Bearbeiten und Absagen nur, solange der Schlüssel noch nicht abgeholt ist. */
    public function update(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id && $reservation->status === ReservationStatus::Reserved;
    }

    /** Löschen nur bei abgesagten Terminen. */
    public function delete(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id && $reservation->status === ReservationStatus::Cancelled;
    }

    /** „Schlüssel liegt im Postkasten“ melden. */
    public function reportKey(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id && $reservation->status === ReservationStatus::Issued;
    }

    /** Tagebuch nur für eigene, vergangene, nicht abgesagte Saunagänge. */
    public function writeDiary(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id
            && $reservation->status !== ReservationStatus::Cancelled
            && $reservation->hasEnded(CarbonImmutable::today());
    }
}
