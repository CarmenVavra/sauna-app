<?php

namespace App\Actions;

use App\Models\DiaryEntry;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

/**
 * Speichert (oder ersetzt) den Tagebucheintrag zu einem Saunagang.
 */
class SaveDiaryEntry
{
    /**
     * @param  array{coins:int, temperature:int, sessions:int, infusion:bool, dosage?:string|null, oil_note?:string|null, note?:string|null, oils?:list<array{name:string, drops:int}>}  $data
     */
    public function handle(Reservation $reservation, array $data): DiaryEntry
    {
        return DB::transaction(function () use ($reservation, $data) {
            $infusion = (bool) $data['infusion'];

            $entry = DiaryEntry::query()->updateOrCreate(
                ['reservation_id' => $reservation->id],
                [
                    'coins' => (int) $data['coins'],
                    'temperature' => (int) $data['temperature'],
                    'sessions' => (int) $data['sessions'],
                    'infusion' => $infusion,
                    'dosage' => $infusion ? ($data['dosage'] ?? null) : null,
                    'oil_note' => $infusion ? ($data['oil_note'] ?? null) : null,
                    'note' => $data['note'] ?? null,
                ],
            );

            $entry->oils()->delete();
            if ($infusion) {
                $oils = collect($data['oils'] ?? [])
                    ->unique(fn ($o) => mb_strtolower(trim($o['name'])))
                    ->map(fn ($o) => ['name' => trim($o['name']), 'drops' => max(1, (int) $o['drops'])])
                    ->values()
                    ->all();
                $entry->oils()->createMany($oils);
            }

            return $entry;
        });
    }
}
