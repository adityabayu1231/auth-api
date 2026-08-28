<?php

use App\Models\Cafe;
use App\Models\Product;

it('does not crash when per_page is non-numeric on cafe list', function () {
    Cafe::factory()->count(3)->create();

    /** @var \Tests\TestCase $this */
    $response = $this->getJson('/api/cafes?per_page=abc');

    $response->assertOk();
    $response->assertJsonPath('success', true);
});

it('clamps per_page above 50 to 50 on cafe list', function () {
    Cafe::factory()->count(3)->create();

    /** @var \Tests\TestCase $this */
    $response = $this->getJson('/api/cafes?per_page=99999');

    $response->assertOk();
    $response->assertJsonPath('data.pagination.per_page', 50);
});

it('falls back to default sort when sort column is invalid', function () {
    Cafe::factory()->count(3)->create();

    /** @var \Tests\TestCase $this */
    $response = $this->getJson('/api/cafes?sort=nonexistent_column');

    $response->assertOk();
    $response->assertJsonPath('success', true);
});

it('does not crash when per_page is non-numeric on product catalog list', function () {
    $cafe = Cafe::factory()->create();
    Product::factory()->count(3)->create(['cafe_id' => $cafe->id, 'is_available' => true]);

    /** @var \Tests\TestCase $this */
    $response = $this->getJson("/api/cafes/{$cafe->id}/products?per_page=abc");

    $response->assertOk();
    $response->assertJsonPath('success', true);
});
