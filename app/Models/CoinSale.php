<?php

namespace App\Models;

use App\Casts\DateOnly;
use Carbon\CarbonImmutable;
use Database\Factories\CoinSaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Münzverkauf, vom Hauswart erfasst.
 *
 * @property int $id
 * @property int $user_id
 * @property CarbonImmutable $sold_on
 * @property int $quantity
 * @property float $unit_price
 */
class CoinSale extends Model
{
    /** @use HasFactory<CoinSaleFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sold_on',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'sold_on' => DateOnly::class,
            'quantity' => 'integer',
            'unit_price' => 'float',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function total(): float
    {
        return $this->quantity * $this->unit_price;
    }
}
