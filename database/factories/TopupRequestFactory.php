<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TopupRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'bank_account_id' => BankAccount::factory(),
            'amount' => 100000,
            'unique_code' => $this->faker->unique()->numerify('###'),
            'proof_image_path' => 'topup-proofs/dummy.jpg',
            'status' => 'pending',
            'verified_by' => null,
            'verified_at' => null,
        ];
    }
}
