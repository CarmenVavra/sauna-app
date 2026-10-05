<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Teilt die Saunagänge einer Partei in offen / kommend / vergangen / abgesagt.
 */
class SaunaVisits
{
    /**
     * @return array{openKeys: Collection<int,Reservation>, next: Reservation|null, more: Collection<int,Reservation>, past: Collection<int,Reservation>, cancelled: Collection<int,Reservation>}
     */
    public function overview(User $user, CarbonImmutable $today): array
    {
        $all = $user->reservations()->with('diaryEntry.oils')->orderBy('date')->get();
        $active = $all->filter(fn (Reservation $r) => $r->status !== ReservationStatus::Cancelled);

        $upcoming = $active->reject(fn (Reservation $r) => $r->hasEnded($today))->values();

        return [
            'openKeys' => $active->filter(fn (Reservation $r) => $r->isOverdue($today))->values(),
            'next' => $upcoming->first(),
            'more' => $upcoming->slice(1)->values(),
            'past' => $active->filter(fn (Reservation $r) => $r->hasEnded($today))->reverse()->values(),
            'cancelled' => $all->filter(fn (Reservation $r) => $r->status === ReservationStatus::Cancelled)->values(),
        ];
    }

    /**
     * Vergangene, nicht abgesagte Saunagänge – neueste zuerst.
     *
     * @return Collection<int,Reservation>
     */
    public function past(User $user, CarbonImmutable $today): Collection
    {
        return $user->reservations()->active()->endedBefore($today)
            ->with('diaryEntry.oils')
            ->orderByDesc('date')->orderByDesc('staircase_number')
            ->get();
    }
}
