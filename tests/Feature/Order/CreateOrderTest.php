<?php

use App\Models\Cafe;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\User;
use App\Models\Wallet;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'cafe_manager', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
});

it('creates an order end-to-end and deducts wallet balance', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('customer');
    Wallet::where('user_id', $user->id)->update(['balance' => 100000]);

    $cafe = Cafe::factory()->create();
    $product = Product::factory()->create([
        'cafe_id' => $cafe->id,
        'base_price' => 20000,
        'is_available' => true,
    ]);
    $option = ProductOption::factory()->create([
        'product_id' => $product->id,
        'option_type' => 'size',
        'option_value' => 'Large',
        'extra_price' => 5000,
    ]);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($user)->postJson('/api/orders', [
        'cafe_id' => $cafe->id,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2, 'option_ids' => [$option->id]],
        ],
        'notes' => 'less sugar please',
    ]);

    $response->assertStatus(201)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'cafe_id' => $cafe->id,
        'status' => 'pending',
        'total_amount' => 50000,
        'notes' => 'less sugar please',
    ]);

    $order = Order::where('user_id', $user->id)->first();

    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 25000,
        'subtotal' => 50000,
    ]);

    $orderItem = $order->items->first();
    $this->assertDatabaseHas('order_item_options', [
        'order_item_id' => $orderItem->id,
        'option_type' => 'size',
        'option_value' => 'Large',
        'extra_price' => 5000,
    ]);

    $this->assertEquals(50000, $user->wallet->fresh()->balance);
    $this->assertDatabaseHas('wallet_transactions', [
        'type' => 'payment',
        'amount' => 50000,
        'reference_type' => 'order',
        'reference_id' => $order->id,
        'balance_after' => 50000,
    ]);
});

it('rejects order creation when wallet balance is insufficient', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('customer');
    Wallet::where('user_id', $user->id)->update(['balance' => 10000]);

    $cafe = Cafe::factory()->create();
    $product = Product::factory()->create([
        'cafe_id' => $cafe->id,
        'base_price' => 20000,
        'is_available' => true,
    ]);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($user)->postJson('/api/orders', [
        'cafe_id' => $cafe->id,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1, 'option_ids' => []],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false]);

    $this->assertEquals(0, Order::count());
    $this->assertEquals(10000, $user->wallet->fresh()->balance);
});

it('rejects order creation for unauthenticated user', function () {
    /** @var \Tests\TestCase $this */
    $response = $this->postJson('/api/orders', [
        'cafe_id' => 1,
        'items' => [],
    ]);

    $response->assertStatus(401);
});
