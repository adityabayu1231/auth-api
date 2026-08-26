<?php

namespace Database\Factories;

use App\Models\Cafe;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cafe_id' => Cafe::factory(),
            'category' => fake()->randomElement(['coffee', 'non-coffee', 'snack']),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'base_price' => fake()->numberBetween(10000, 40000),
            'image_path' => null,
            'service_time_minutes' => fake()->numberBetween(3, 15),
            'is_available' => true,
        ];
    }

    /**
     * Indicate that the product is not available.
     */
    public function unavailable(): self
    {
        return $this->state(fn() => ['is_available' => false]);
    }
}
