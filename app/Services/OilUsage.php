<?php

namespace App\Services;

use App\Models\DiaryEntryOil;
use App\Models\User;

/**
 * Wie oft und mit wie vielen Tropfen eine Partei ihre Öle im Aufguss verwendet hat.
 */
class OilUsage
{
    /**
     * @return array<string, array{times:int, drops:int}>
     */
    public function forUser(User $user): array
    {
        return DiaryEntryOil::query()
            ->join('diary_entries', 'diary_entries.id', '=', 'diary_entry_oils.diary_entry_id')
            ->join('reservations', 'reservations.id', '=', 'diary_entries.reservation_id')
            ->where('reservations.user_id', $user->id)
            ->whereNull('reservations.deleted_at')
            ->where('diary_entries.infusion', true)
            ->groupBy('diary_entry_oils.name')
            ->selectRaw('diary_entry_oils.name as name, COUNT(*) as times, SUM(diary_entry_oils.drops) as drops')
            ->toBase()
            ->get()
            ->mapWithKeys(fn ($row) => [(string) $row->name => ['times' => (int) $row->times, 'drops' => (int) $row->drops]])
            ->all();
    }
}
