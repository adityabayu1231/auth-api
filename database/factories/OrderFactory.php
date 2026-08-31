<?php

namespace Database\Factories;

use App\Models\Cafe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'cafe_id' => Cafe::factory(),
            'status' => 'pending',
            'total_amount' => 35000,
            'estimated_ready_at' => null,
            'notes' => null,
        ];
    }
}
