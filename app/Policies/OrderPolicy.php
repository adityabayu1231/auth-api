<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('cafe_manager')) {
            return $user->cafe_id === $order->cafe_id;
        }

        return $user->hasRole('customer') && $user->id === $order->user_id;
    }

    public function updateStatus(User $user, Order $order): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('cafe_manager') && $user->cafe_id === $order->cafe_id;
    }

    public function cancel(User $user, Order $order): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('customer') && $user->id === $order->user_id;
    }
}
