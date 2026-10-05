<?php

namespace App\Models;

use App\Casts\DateOnly;
use Carbon\CarbonImmutable;
use Database\Factories\CoinPurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eigene Aufzeichnung einer Partei über gekaufte Münzen.
 *
 * @property int $id
 * @property int $user_id
 * @property CarbonImmutable $purchased_on
 * @property int $quantity
 * @property float $unit_price
 */
class CoinPurchase extends Model
{
    /** @use HasFactory<CoinPurchaseFactory> */
    use HasFactory;

    protected $fillable = [
        'purchased_on',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'purchased_on' => DateOnly::class,
            'quantity' => 'integer',
            'unit_price' => 'float',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function total(): float
    {
        return $this->quantity * $this->unit_price;
    }
}
