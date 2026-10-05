<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein Öl einer Mischung. Der Name wird gespeichert (wie im Tagebuch), damit die
 * Mischung erhalten bleibt, wenn ein Öl aus „Meine Öle“ entfernt wird.
 *
 * @property int $position
 * @property string $oil_name
 * @property int $drops
 * @property string|null $note
 */
class BlendIngredient extends Model
{
    protected $fillable = [
        'position',
        'oil_name',
        'drops',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'drops' => 'integer',
        ];
    }

    /** @return BelongsTo<Blend, $this> */
    public function blend(): BelongsTo
    {
        return $this->belongsTo(Blend::class);
    }
}
