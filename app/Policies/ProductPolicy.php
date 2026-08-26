<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function create(User $user, int $cafeId): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('cafe_manager') && $user->cafe_id === $cafeId);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('cafe_manager') && $user->cafe_id === $product->cafe_id);
    }
}
