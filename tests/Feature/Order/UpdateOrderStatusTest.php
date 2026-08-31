<?php

use App\Models\Cafe;
use App\Models\Order;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'cafe_manager', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
});

it('lets cafe_manager change status of own cafe order from pending to preparing', function () {
    $cafe = Cafe::factory()->create();
    /** @var User $manager */
    $manager = User::factory()->create(['cafe_id' => $cafe->id]);
    $manager->assignRole('cafe_manager');
    $order = Order::factory()->create(['cafe_id' => $cafe->id, 'status' => 'pending']);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($manager)->patchJson("/api/orders/{$order->id}/status", [
        'status' => 'preparing',
    ]);

    $response->assertOk()->assertJsonPath('data.status', 'preparing');
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'preparing']);
});

it('rejects invalid status transition from pending directly to finished', function () {
    $cafe = Cafe::factory()->create();
    /** @var User $manager */
    $manager = User::factory()->create(['cafe_id' => $cafe->id]);
    $manager->assignRole('cafe_manager');
    $order = Order::factory()->create(['cafe_id' => $cafe->id, 'status' => 'pending']);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($manager)->patchJson("/api/orders/{$order->id}/status", [
        'status' => 'finished',
    ]);

    $response->assertStatus(422)->assertJson(['success' => false]);
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
});

it('rejects cafe_manager updating status of order from other cafe', function () {
    $ownCafe = Cafe::factory()->create();
    $otherCafe = Cafe::factory()->create();
    /** @var User $manager */
    $manager = User::factory()->create(['cafe_id' => $ownCafe->id]);
    $manager->assignRole('cafe_manager');
    $order = Order::factory()->create(['cafe_id' => $otherCafe->id, 'status' => 'pending']);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($manager)->patchJson("/api/orders/{$order->id}/status", [
        'status' => 'preparing',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
});

it('lets admin change status of any order', function () {
    $cafe = Cafe::factory()->create();
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $order = Order::factory()->create(['cafe_id' => $cafe->id, 'status' => 'pending']);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($admin)->patchJson("/api/orders/{$order->id}/status", [
        'status' => 'preparing',
    ]);

    $response->assertOk();
});
