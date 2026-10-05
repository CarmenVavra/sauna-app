<?php

namespace App\Models;

use App\Support\EffectText;
use Database\Factories\OilFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein ätherisches Öl aus der persönlichen Liste einer Partei.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $brand
 * @property string|null $note
 * @property string|null $effect
 */
class Oil extends Model
{
    /** @use HasFactory<OilFactory> */
    use HasFactory;

    /** Laravel hält „oil“ für unzählbar, daher explizit. */
    protected $table = 'oils';

    protected $fillable = [
        'name',
        'brand',
        'note',
        'effect',
    ];

    /**
     * Wirkung als Punkte für die Anzeige.
     *
     * @return list<array{bullet: bool, title: string|null, text: string}>
     */
    public function effectPoints(): array
    {
        return EffectText::points($this->effect);
    }

    /** Kurzfassung der Wirkung für die Listenzeile. */
    public function effectSummary(): string
    {
        return EffectText::summary($this->effect);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
