<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Ein Zugang: der Hauswart (Admin) oder eine Partei (Bewohner).
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $username
 * @property Role $role
 * @property int|null $staircase_number
 * @property int|null $apartment
 * @property bool $must_change_password
 * @property-read string $name
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'password',
        'role',
        'staircase_number',
        'apartment',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Role::class,
            'staircase_number' => 'integer',
            'apartment' => 'integer',
            'must_change_password' => 'boolean',
        ];
    }

    /** @return Attribute<string, never> */
    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->first_name.' '.$this->last_name));
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /** „Stiege 3 · Top 7“ */
    public function location(): string
    {
        return $this->staircase_number ? 'Stiege '.$this->staircase_number.' · Top '.$this->apartment : '';
    }

    /** „St. 3 · Top 7“ */
    public function shortLocation(): string
    {
        return $this->staircase_number ? 'St. '.$this->staircase_number.' · Top '.$this->apartment : '';
    }

    /** @param Builder<User> $query */
    public function scopeParties(Builder $query): void
    {
        $query->where('role', Role::User->value);
    }

    /** @return BelongsTo<Staircase, $this> */
    public function staircase(): BelongsTo
    {
        return $this->belongsTo(Staircase::class, 'staircase_number', 'number');
    }

    /** @return HasMany<Reservation, $this> */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /** @return HasMany<CoinSale, $this> */
    public function coinSales(): HasMany
    {
        return $this->hasMany(CoinSale::class);
    }

    /** @return HasMany<CoinPurchase, $this> */
    public function coinPurchases(): HasMany
    {
        return $this->hasMany(CoinPurchase::class);
    }

    /** @return HasMany<Oil, $this> */
    public function oils(): HasMany
    {
        return $this->hasMany(Oil::class)->orderBy('id');
    }

    /** @return HasMany<Blend, $this> */
    public function blends(): HasMany
    {
        return $this->hasMany(Blend::class)->orderBy('name');
    }
}
