<?php

namespace App\Policies;

use App\Models\Cafe;
use App\Models\User;

class CafePolicy
{
    public function update(User $user, Cafe $cafe): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('cafe_manager') && $user->cafe_id === $cafe->id);
    }
}
