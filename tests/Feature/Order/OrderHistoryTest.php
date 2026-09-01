<?php

use App\Models\Cafe;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'cafe_manager', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
});

it('lists only own orders for customer with pagination format', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('customer');
    /** @var User $otherUser */
    $otherUser = User::factory()->create();

    Order::factory()->count(3)->create(['user_id' => $user->id]);
    Order::factory()->count(2)->create(['user_id' => $otherUser->id]);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($user)->getJson('/api/orders');

    $response->assertOk()
        ->assertJsonPath('data.pagination.total', 3)
        ->assertJsonCount(3, 'data.items');
});

it('shows order detail with items and options for owner customer', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('customer');
    $order = Order::factory()->create(['user_id' => $user->id]);
    $item = OrderItem::factory()->create(['order_id' => $order->id]);
    OrderItemOption::factory()->create(['order_item_id' => $item->id]);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($user)->getJson("/api/orders/{$order->id}");

    $response->assertOk()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonCount(1, 'data.items.0.options');
});

it('rejects customer viewing order that belongs to another customer', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    /** @var User $intruder */
    $intruder = User::factory()->create();
    $intruder->assignRole('customer');
    $order = Order::factory()->create(['user_id' => $owner->id]);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($intruder)->getJson("/api/orders/{$order->id}");

    $response->assertForbidden();
});

it('lets cafe_manager view order detail of own cafe', function () {
    $cafe = Cafe::factory()->create();
    /** @var User $manager */
    $manager = User::factory()->create(['cafe_id' => $cafe->id]);
    $manager->assignRole('cafe_manager');
    $order = Order::factory()->create(['cafe_id' => $cafe->id]);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($manager)->getJson("/api/orders/{$order->id}");

    $response->assertOk();
});

it('rejects cafe_manager viewing order of other cafe', function () {
    $ownCafe = Cafe::factory()->create();
    $otherCafe = Cafe::factory()->create();
    /** @var User $manager */
    $manager = User::factory()->create(['cafe_id' => $ownCafe->id]);
    $manager->assignRole('cafe_manager');
    $order = Order::factory()->create(['cafe_id' => $otherCafe->id]);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($manager)->getJson("/api/orders/{$order->id}");

    $response->assertForbidden();
});

it('lets admin view any order detail', function () {
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $order = Order::factory()->create();

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($admin)->getJson("/api/orders/{$order->id}");

    $response->assertOk();
});
