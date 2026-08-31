<?php

namespace Database\Factories;

use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemOptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'option_type' => 'size',
            'option_value' => 'Large',
            'extra_price' => 5000,
        ];
    }
}
