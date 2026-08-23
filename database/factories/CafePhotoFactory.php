<?php

namespace Database\Factories;

use App\Models\CafePhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CafePhoto>
 */
class CafePhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cafe_id' => \App\Models\Cafe::factory(),
            'photo_path' => 'cafe-photos/' . fake()->uuid() . '.jpg',
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
