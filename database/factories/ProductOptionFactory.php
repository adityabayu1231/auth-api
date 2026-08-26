<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductOption>
 */
class ProductOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'option_type' => 'size',
            'option_value' => 'Regular',
            'extra_price' => 0,
            'is_default' => false,
        ];
    }

    /**
     * Indicate that this option is the default for its type.
     */
    public function default(): self
    {
        return $this->state(fn() => ['is_default' => true]);
    }
}
