<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Enums\ReservationStatus;
use App\Support\Block;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

/**
 * Eine Reservierung gilt den ganzen Tag (bzw. das ganze Wochenende,
 * dann mit dem Samstag als Datum).
 *
 * @property int $id
 * @property int $user_id
 * @property int $staircase_number
 * @property CarbonImmutable $date
 * @property bool $is_weekend
 * @property ReservationStatus $status
 * @property int|null $coins_collected
 * @property string|null $note
 */
class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'staircase_number',
        'date',
        'is_weekend',
        'status',
        'coins_collected',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => DateOnly::class,
            'is_weekend' => 'boolean',
            'status' => ReservationStatus::class,
            'staircase_number' => 'integer',
            'coins_collected' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /** @return BelongsTo<Staircase, $this> */
    public function staircase(): BelongsTo
    {
        return $this->belongsTo(Staircase::class, 'staircase_number', 'number');
    }

    /** @return HasOne<DiaryEntry, $this> */
    public function diaryEntry(): HasOne
    {
        return $this->hasOne(DiaryEntry::class);
    }

    /** @param Builder<Reservation> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', '!=', ReservationStatus::Cancelled->value);
    }

    /**
     * Ende (bei Wochenenden der Sonntag) liegt vor dem Stichtag.
     *
     * @param  Builder<Reservation>  $query
     */
    public function scopeEndedBefore(Builder $query, CarbonInterface $day): void
    {
        $query->where(function (Builder $q) use ($day) {
            $q->where(fn (Builder $w) => $w->where('is_weekend', false)->where('date', '<', $day->toDateString()))
                ->orWhere(fn (Builder $w) => $w->where('is_weekend', true)->where('date', '<', $day->copy()->subDay()->toDateString()));
        });
    }

    /** @param Builder<Reservation> $query */
    public function scopeChronological(Builder $query): void
    {
        $query->orderBy('date')->orderBy('staircase_number');
    }

    /**
     * Schlüssel-Status weiterschalten; unzulässige Schritte werden abgelehnt.
     *
     * @throws ValidationException
     */
    public function moveTo(ReservationStatus $to): void
    {
        if (! $this->status->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'status' => 'Dieser Schritt ist im Status „'.$this->status->label().'“ nicht möglich.',
            ]);
        }

        $this->update(['status' => $to]);
    }

    /**
     * Eingesammelte Münzen erfassen – erst wenn der Schlüssel zurück ist.
     *
     * @throws ValidationException
     */
    public function recordCoins(int $coins): void
    {
        if ($this->status !== ReservationStatus::Returned) {
            throw ValidationException::withMessages([
                'coins' => 'Münzen können erst erfasst werden, wenn der Schlüssel zurück ist.',
            ]);
        }

        $this->update(['coins_collected' => $coins]);
    }

    /**
     * Statusaktionen des Hauswarts im Detaildialog: [Zielstatus, Beschriftung, Button-Klasse].
     *
     * @return list<array{0:string, 1:string, 2:string}>
     */
    public function adminActions(): array
    {
        return match ($this->status) {
            ReservationStatus::Reserved => [[ReservationStatus::Issued->value, 'Schlüssel ausgegeben', 'btn-primary']],
            ReservationStatus::Issued => [[ReservationStatus::Returned->value, 'Schlüssel ist im Postkasten', 'btn-primary']],
            ReservationStatus::Reported => [
                [ReservationStatus::Issued->value, 'Nicht im Postkasten', 'btn-secondary'],
                [ReservationStatus::Returned->value, 'Bestätigen', 'btn-primary'],
            ],
            default => [],
        };
    }

    public function block(): Block
    {
        return new Block($this->date, $this->is_weekend);
    }

    public function endDate(): CarbonImmutable
    {
        return $this->block()->end();
    }

    public function hasEnded(CarbonInterface $today): bool
    {
        return $this->endDate()->lt(CarbonImmutable::instance($today)->startOfDay());
    }

    public function isOverdue(CarbonInterface $today): bool
    {
        return $this->status === ReservationStatus::Issued && $this->hasEnded($today);
    }

    public function statusLabel(CarbonInterface $today): string
    {
        return $this->isOverdue($today) ? 'Überfällig' : $this->status->label();
    }

    public function statusClass(CarbonInterface $today): string
    {
        return $this->isOverdue($today) ? 'tag-accent-2' : $this->status->tagClass();
    }

    public function when(): string
    {
        return $this->block()->when();
    }

    public function whenLong(): string
    {
        return $this->block()->whenLong();
    }

    public function saunaLabel(): string
    {
        return 'Stiege '.$this->staircase_number;
    }
}
