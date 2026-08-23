<?php

namespace Database\Factories;

use App\Models\CafeOperatingHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CafeOperatingHour>
 */
class CafeOperatingHourFactory extends Factory
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
            'day_of_week' => fake()->numberBetween(0, 6),
            'open_time' => '08:00:00',
            'close_time' => '20:00:00',
            'is_closed' => false,
        ];
    }
}
