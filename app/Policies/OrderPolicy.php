<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function updateStatus(User $user, Order $order): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('cafe_manager') && $user->cafe_id === $order->cafe_id;
    }
}
