<?php

use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'cafe_manager', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
});

it('returns own wallet balance and transaction history', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('customer');
    Wallet::where('user_id', $user->id)->update(['balance' => 50000]);
    $order = Order::factory()->create(['user_id' => $user->id]);

    (new WalletService())->pay($user, $order, 20000);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($user)->getJson('/api/wallet');

    $response->assertOk()
        ->assertJsonPath('data.balance', 30000)
        ->assertJsonPath('data.transactions.pagination.total', 1)
        ->assertJsonCount(1, 'data.transactions.items');
});

it('does not include other users transactions', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('customer');
    /** @var User $otherUser */
    $otherUser = User::factory()->create();
    $otherOrder = Order::factory()->create(['user_id' => $otherUser->id]);
    Wallet::where('user_id', $otherUser->id)->update(['balance' => 100000]);
    (new WalletService())->pay($otherUser, $otherOrder, 10000);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($user)->getJson('/api/wallet');

    $response->assertOk()
        ->assertJsonPath('data.balance', 0)
        ->assertJsonCount(0, 'data.transactions.items');
});

it('rejects unauthenticated access to wallet', function () {
    /** @var \Tests\TestCase $this */
    $response = $this->getJson('/api/wallet');

    $response->assertStatus(401);
});
