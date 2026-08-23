<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use Illuminate\Database\Seeder;

class BankAccountSeeder extends Seeder
{
    public function run(): void
    {
        BankAccount::create([
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Awake Coffee',
        ]);

        BankAccount::create([
            'bank_name' => 'Mandiri',
            'account_number' => '0987654321',
            'account_holder' => 'Awake Coffee',
        ]);
    }
}
