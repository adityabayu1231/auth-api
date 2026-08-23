<?php

namespace Database\Factories;

use App\Models\Cafe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cafe>
 */
class CafeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Awake Coffee - ' . fake()->city(),
            'city' => fake()->city(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(-8, -6),
            'longitude' => fake()->longitude(106, 108),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
