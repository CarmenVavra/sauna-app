<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein im Aufguss verwendetes Öl mit Tropfenzahl. Der Name wird kopiert,
 * damit Einträge erhalten bleiben, wenn ein Öl aus der Liste entfernt wird.
 *
 * @property string $name
 * @property int $drops
 */
class DiaryEntryOil extends Model
{
    /** Laravel hält „oil“ für unzählbar, daher explizit. */
    protected $table = 'diary_entry_oils';

    protected $fillable = [
        'name',
        'drops',
    ];

    protected function casts(): array
    {
        return [
            'drops' => 'integer',
        ];
    }

    /** @return BelongsTo<DiaryEntry, $this> */
    public function diaryEntry(): BelongsTo
    {
        return $this->belongsTo(DiaryEntry::class);
    }
}
