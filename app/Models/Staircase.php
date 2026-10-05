<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Carbon\CarbonImmutable;
use Database\Factories\StaircaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eine Stiege der Wohnanlage, identifiziert über ihre Nummer (1–15).
 *
 * @property int $number
 * @property int $apartments
 * @property bool $has_sauna
 */
class Staircase extends Model
{
    /** @use HasFactory<StaircaseFactory> */
    use HasFactory;

    protected $primaryKey = 'number';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'number',
        'apartments',
        'has_sauna',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'apartments' => 'integer',
            'has_sauna' => 'boolean',
        ];
    }

    /** @return HasMany<User, $this> */
    public function parties(): HasMany
    {
        return $this->hasMany(User::class, 'staircase_number', 'number')->where('role', 'user');
    }

    /** @return HasMany<Reservation, $this> */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'staircase_number', 'number');
    }

    /** @param Builder<Staircase> $query */
    public function scopeWithSauna(Builder $query): void
    {
        $query->where('has_sauna', true)->orderBy('number');
    }

    /** Storniert alle offenen künftigen Reservierungen (z. B. wenn die Sauna stillgelegt wird). */
    public function cancelFutureReservations(): int
    {
        return $this->reservations()
            ->where('status', ReservationStatus::Reserved->value)
            ->where('date', '>=', CarbonImmutable::today()->toDateString())
            ->update(['status' => ReservationStatus::Cancelled->value]);
    }

    public function label(): string
    {
        return 'Stiege '.$this->number;
    }
}
