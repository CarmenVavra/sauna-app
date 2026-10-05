<?php

namespace App\Models;

use Database\Factories\BlendFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eine gespeicherte Aufguss-Mischung einer Partei (mehrere Öle mit Tropfenzahl).
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 */
class Blend extends Model
{
    /** @use HasFactory<BlendFactory> */
    use HasFactory;

    /** Empfehlung: 5 bis 10 Tropfen auf 1 Liter Aufgusswasser. */
    public const MAX_RECOMMENDED_DROPS = 10;

    protected $fillable = [
        'name',
        'description',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<BlendIngredient, $this> */
    public function ingredients(): HasMany
    {
        return $this->hasMany(BlendIngredient::class)->orderBy('position');
    }

    public function totalDrops(): int
    {
        return (int) $this->ingredients->sum('drops');
    }

    public function isTooStrong(): bool
    {
        return $this->totalDrops() > self::MAX_RECOMMENDED_DROPS;
    }

    /**
     * Für Tagebuch und Bearbeiten-Formular.
     *
     * @return array{id: int, name: string, ingredients: list<array{name: string, drops: int, note: string|null}>}
     */
    public function toEditorArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'ingredients' => $this->ingredients
                ->map(fn (BlendIngredient $i) => ['name' => $i->oil_name, 'drops' => $i->drops, 'note' => $i->note])
                ->values()->all(),
        ];
    }
}
