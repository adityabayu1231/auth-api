<?php

use App\Models\Cafe;
use App\Models\Product;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'cafe_manager', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
});

it('admin can create product for any cafe', function () {
    $cafe = Cafe::factory()->create();
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($admin)->postJson('/api/products', [
        'cafe_id' => $cafe->id,
        'category' => 'coffee',
        'name' => 'Cappuccino',
        'base_price' => 25000,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Cappuccino')
        ->assertJsonPath('data.is_available', true);

    $this->assertDatabaseHas('products', ['name' => 'Cappuccino', 'cafe_id' => $cafe->id]);
});

it('cafe_manager can create product for own cafe', function () {
    $cafe = Cafe::factory()->create();
    /** @var User $manager */
    $manager = User::factory()->create(['cafe_id' => $cafe->id]);
    $manager->assignRole('cafe_manager');

    /** @var \Tests\TestCase $this */
    $this->actingAs($manager)->postJson('/api/products', [
        'cafe_id' => $cafe->id,
        'category' => 'coffee',
        'name' => 'Latte',
        'base_price' => 22000,
    ])->assertCreated();
});

it('cafe_manager cannot create product for other cafe', function () {
    $ownCafe = Cafe::factory()->create();
    $otherCafe = Cafe::factory()->create();
    /** @var User $manager */
    $manager = User::factory()->create(['cafe_id' => $ownCafe->id]);
    $manager->assignRole('cafe_manager');

    /** @var \Tests\TestCase $this */
    $this->actingAs($manager)->postJson('/api/products', [
        'cafe_id' => $otherCafe->id,
        'category' => 'coffee',
        'name' => 'Americano',
        'base_price' => 20000,
    ])->assertForbidden();
});

it('rejects product creation with invalid category', function () {
    $cafe = Cafe::factory()->create();
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    /** @var \Tests\TestCase $this */
    $this->actingAs($admin)->postJson('/api/products', [
        'cafe_id' => $cafe->id,
        'category' => 'dessert', // bukan enum valid
        'name' => 'Cheesecake',
        'base_price' => 30000,
    ])->assertStatus(422);
});

it('cafe_manager can update own product', function () {
    $cafe = Cafe::factory()->create();
    $product = Product::factory()->create(['cafe_id' => $cafe->id]);
    /** @var User $manager */
    $manager = User::factory()->create(['cafe_id' => $cafe->id]);
    $manager->assignRole('cafe_manager');

    /** @var \Tests\TestCase $this */
    $this->actingAs($manager)->putJson("/api/products/{$product->id}", [
        'is_available' => false,
    ])->assertOk()
        ->assertJsonPath('data.is_available', false);
});

it('cafe_manager cannot update product of other cafe', function () {
    $ownCafe = Cafe::factory()->create();
    $otherCafe = Cafe::factory()->create();
    $product = Product::factory()->create(['cafe_id' => $otherCafe->id]);
    /** @var User $manager */
    $manager = User::factory()->create(['cafe_id' => $ownCafe->id]);
    $manager->assignRole('cafe_manager');

    /** @var \Tests\TestCase $this */
    $this->actingAs($manager)->putJson("/api/products/{$product->id}", [
        'name' => 'Nama Nakal',
    ])->assertForbidden();
});
