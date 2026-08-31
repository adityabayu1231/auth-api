<?php

use App\Models\Cafe;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'cafe_manager', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
});

it('lets customer cancel own pending order and receives refund', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('customer');
    Wallet::where('user_id', $user->id)->update(['balance' => 15000]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
        'total_amount' => 35000,
    ]);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($user)->patchJson("/api/orders/{$order->id}/cancel");

    $response->assertOk()->assertJsonPath('data.status', 'cancelled');
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
    $this->assertEquals(50000, $user->wallet->fresh()->balance);
});

it('rejects cancelling a finished order', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('customer');
    $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'finished']);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($user)->patchJson("/api/orders/{$order->id}/cancel");

    $response->assertStatus(422)->assertJson(['success' => false]);
});

it('rejects double cancel to prevent double refund', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('customer');
    $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'cancelled']);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($user)->patchJson("/api/orders/{$order->id}/cancel");

    $response->assertStatus(422)->assertJson(['success' => false]);
});

it('rejects customer cancelling order that belongs to another customer', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('customer');
    /** @var User $intruder */
    $intruder = User::factory()->create();
    $intruder->assignRole('customer');
    $order = Order::factory()->create(['user_id' => $owner->id, 'status' => 'pending']);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($intruder)->patchJson("/api/orders/{$order->id}/cancel");

    $response->assertForbidden();
});

it('lets admin cancel any order', function () {
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $order = Order::factory()->create(['status' => 'preparing']);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($admin)->patchJson("/api/orders/{$order->id}/cancel");

    $response->assertOk();
});
