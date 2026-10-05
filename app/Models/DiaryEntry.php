<?php

namespace App\Models;

use App\Enums\Dosage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Saunatagebuch-Eintrag zu einem vergangenen Saunagang.
 *
 * @property int $id
 * @property int $reservation_id
 * @property int $coins
 * @property int $temperature
 * @property int $sessions
 * @property bool $infusion
 * @property Dosage|null $dosage
 * @property string|null $oil_note
 * @property string|null $note
 */
class DiaryEntry extends Model
{
    protected $fillable = [
        'reservation_id',
        'coins',
        'temperature',
        'sessions',
        'infusion',
        'dosage',
        'oil_note',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'coins' => 'integer',
            'temperature' => 'integer',
            'sessions' => 'integer',
            'infusion' => 'boolean',
            'dosage' => Dosage::class,
        ];
    }

    /** @return BelongsTo<Reservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /** @return HasMany<DiaryEntryOil, $this> */
    public function oils(): HasMany
    {
        return $this->hasMany(DiaryEntryOil::class)->orderBy('id');
    }
}
