<?php

use App\Models\Cafe;
use App\Models\Product;
use App\Models\ProductOption;

it('hides unavailable products from cafe catalog', function () {
    $cafe = Cafe::factory()->create();
    Product::factory()->count(8)->create(['cafe_id' => $cafe->id, 'is_available' => true]);
    Product::factory()->count(2)->create(['cafe_id' => $cafe->id, 'is_available' => false]);

    /** @var \Tests\TestCase $this */
    $response = $this->getJson("/api/cafes/{$cafe->id}/products");

    $response->assertOk()
        ->assertJsonCount(8, 'data.items')
        ->assertJsonStructure(['data' => ['pagination' => ['current_page', 'per_page', 'total', 'last_page']]]);
});

it('does not return products from other cafes', function () {
    $cafeA = Cafe::factory()->create();
    $cafeB = Cafe::factory()->create();
    Product::factory()->count(2)->create(['cafe_id' => $cafeA->id]);
    Product::factory()->count(3)->create(['cafe_id' => $cafeB->id]);

    /** @var \Tests\TestCase $this */
    $this->getJson("/api/cafes/{$cafeA->id}/products")
        ->assertOk()
        ->assertJsonCount(2, 'data.items');
});

it('shows product detail including its options', function () {
    $product = Product::factory()->create();
    ProductOption::factory()->count(2)->create(['product_id' => $product->id]);

    /** @var \Tests\TestCase $this */
    $response = $this->getJson("/api/products/{$product->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonCount(2, 'data.options');
});

it('returns 404 for nonexistent product', function () {
    /** @var \Tests\TestCase $this */
    $this->getJson('/api/products/999999')->assertNotFound();
});
