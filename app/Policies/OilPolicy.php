<?php

namespace App\Policies;

use App\Models\Oil;
use App\Models\User;

class OilPolicy
{
    public function update(User $user, Oil $oil): bool
    {
        return $oil->user_id === $user->id;
    }

    public function delete(User $user, Oil $oil): bool
    {
        return $oil->user_id === $user->id;
    }
}
