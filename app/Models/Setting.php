<?php

namespace App\Models;

use App\Support\Fmt;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Grundwerte der Anlage (genau eine Zeile).
 *
 * @property float $coin_price
 * @property int $minutes_per_coin
 * @property int $coins_total
 * @property string $mon_thu_from
 * @property string $mon_thu_until
 * @property string $fri_from
 * @property string $fri_until
 */
class Setting extends Model
{
    protected $fillable = [
        'coin_price',
        'minutes_per_coin',
        'coins_total',
        'mon_thu_from',
        'mon_thu_until',
        'fri_from',
        'fri_until',
    ];

    protected function casts(): array
    {
        return [
            'coin_price' => 'float',
            'minutes_per_coin' => 'integer',
            'coins_total' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create(config('sauna.defaults'));
    }

    public function monThuLabel(): string
    {
        return 'Mo–Do '.$this->mon_thu_from.'–'.$this->mon_thu_until;
    }

    public function friLabel(): string
    {
        return 'Fr '.$this->fri_from.'–'.$this->fri_until;
    }

    public function coinLabel(): string
    {
        return '1 Münze = '.$this->minutes_per_coin.' Minuten · '.Fmt::eur($this->coin_price);
    }

    public function pickupToday(CarbonInterface $today): string
    {
        return match (true) {
            $today->dayOfWeek >= CarbonInterface::MONDAY && $today->dayOfWeek <= CarbonInterface::THURSDAY => 'Schlüsselausgabe heute '.$this->mon_thu_from.'–'.$this->mon_thu_until,
            $today->dayOfWeek === CarbonInterface::FRIDAY => 'Schlüsselausgabe heute '.$this->fri_from.'–'.$this->fri_until,
            default => 'Heute keine Schlüsselausgabe',
        };
    }
}
