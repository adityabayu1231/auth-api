<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'bank_name' => 'BCA',
            'account_number' => $this->faker->numerify('##########'),
            'account_holder' => $this->faker->name(),
        ];
    }
}
