<?php

namespace App\Policies;

use App\Models\CoinPurchase;
use App\Models\User;

class CoinPurchasePolicy
{
    public function delete(User $user, CoinPurchase $coinPurchase): bool
    {
        return $coinPurchase->user_id === $user->id;
    }
}
