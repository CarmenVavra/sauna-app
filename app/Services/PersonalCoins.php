<?php

namespace App\Services;

use App\Models\DiaryEntry;
use App\Models\Setting;
use App\Models\User;

/**
 * Münzstand aus Sicht einer Partei: eigene Käufe minus laut Tagebuch verbraucht.
 */
class PersonalCoins
{
    /**
     * @return array{bought:int, used:int, left:int, spent:float, avgPrice:float, low:bool}
     */
    public function forUser(User $user, Setting $settings): array
    {
        $row = $user->coinPurchases()
            ->selectRaw('COALESCE(SUM(quantity), 0) as bought, COALESCE(SUM(quantity * unit_price), 0) as spent')
            ->toBase()
            ->first();

        $bought = (int) ($row->bought ?? 0);
        $spent = (float) ($row->spent ?? 0);
        $used = (int) DiaryEntry::query()
            ->whereHas('reservation', fn ($q) => $q->where('user_id', $user->id))
            ->sum('coins');

        return [
            'bought' => $bought,
            'used' => $used,
            'left' => $bought - $used,
            'spent' => $spent,
            'avgPrice' => $bought > 0 ? $spent / $bought : $settings->coin_price,
            'low' => $bought - $used < 3,
        ];
    }
}
