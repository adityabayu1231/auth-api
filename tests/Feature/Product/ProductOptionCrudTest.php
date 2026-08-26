<?php

use App\Models\Cafe;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'cafe_manager', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
});

it('cafe_manager can add options to own product', function () {
    $cafe = Cafe::factory()->create();
    $product = Product::factory()->create(['cafe_id' => $cafe->id]);
    /** @var User $manager */
    $manager = User::factory()->create(['cafe_id' => $cafe->id]);
    $manager->assignRole('cafe_manager');

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($manager)->postJson("/api/products/{$product->id}/options", [
        'options' => [
            ['option_type' => 'size', 'option_value' => 'Regular', 'extra_price' => 0, 'is_default' => false],
            ['option_type' => 'size', 'option_value' => 'Large', 'extra_price' => 5000, 'is_default' => true],
            ['option_type' => 'sweetness', 'option_value' => '50%', 'extra_price' => 0, 'is_default' => true],
        ],
    ]);

    $response->assertCreated();
    $this->assertDatabaseCount('product_options', 3);
});

it('rejects more than one default option per option_type in one request', function () {
    $cafe = Cafe::factory()->create();
    $product = Product::factory()->create(['cafe_id' => $cafe->id]);
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    /** @var \Tests\TestCase $this */
    $this->actingAs($admin)->postJson("/api/products/{$product->id}/options", [
        'options' => [
            ['option_type' => 'sweetness', 'option_value' => '0%', 'extra_price' => 0, 'is_default' => true],
            ['option_type' => 'sweetness', 'option_value' => '50%', 'extra_price' => 0, 'is_default' => true],
        ],
    ])->assertStatus(422);
});

it('cafe_manager cannot add options to product of other cafe', function () {
    $ownCafe = Cafe::factory()->create();
    $otherCafe = Cafe::factory()->create();
    $product = Product::factory()->create(['cafe_id' => $otherCafe->id]);
    /** @var User $manager */
    $manager = User::factory()->create(['cafe_id' => $ownCafe->id]);
    $manager->assignRole('cafe_manager');

    /** @var \Tests\TestCase $this */
    $this->actingAs($manager)->postJson("/api/products/{$product->id}/options", [
        'options' => [
            ['option_type' => 'size', 'option_value' => 'Regular', 'extra_price' => 0],
        ],
    ])->assertForbidden();
});

it('cafe_manager can update own product option and unsets previous default', function () {
    $cafe = Cafe::factory()->create();
    $product = Product::factory()->create(['cafe_id' => $cafe->id]);
    $oldDefault = ProductOption::factory()->create([
        'product_id' => $product->id,
        'option_type' => 'sweetness',
        'option_value' => '50%',
        'is_default' => true,
    ]);
    $target = ProductOption::factory()->create([
        'product_id' => $product->id,
        'option_type' => 'sweetness',
        'option_value' => '100%',
        'is_default' => false,
    ]);
    /** @var User $manager */
    $manager = User::factory()->create(['cafe_id' => $cafe->id]);
    $manager->assignRole('cafe_manager');

    /** @var \Tests\TestCase $this */
    $this->actingAs($manager)->putJson("/api/product-options/{$target->id}", [
        'is_default' => true,
    ])->assertOk()
        ->assertJsonPath('data.is_default', true);

    expect($oldDefault->fresh()->is_default)->toBeFalse();
});

it('cafe_manager cannot update option of product from other cafe', function () {
    $ownCafe = Cafe::factory()->create();
    $otherCafe = Cafe::factory()->create();
    $product = Product::factory()->create(['cafe_id' => $otherCafe->id]);
    $option = ProductOption::factory()->create(['product_id' => $product->id]);
    /** @var User $manager */
    $manager = User::factory()->create(['cafe_id' => $ownCafe->id]);
    $manager->assignRole('cafe_manager');

    /** @var \Tests\TestCase $this */
    $this->actingAs($manager)->putJson("/api/product-options/{$option->id}", [
        'option_value' => 'Nakal',
    ])->assertForbidden();
});
