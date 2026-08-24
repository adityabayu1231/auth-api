<?php

use App\Models\Cafe;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'cafe_manager', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
});

it('lists cafes with pagination format', function () {
    Cafe::factory()->count(3)->create(['city' => 'Jakarta']);
    Cafe::factory()->count(2)->create(['city' => 'Bandung']);

    /** @var \Tests\TestCase $this */
    $response = $this->getJson('/api/cafes?city=Jakarta');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data.items')
        ->assertJsonStructure(['data' => ['pagination' => ['current_page', 'per_page', 'total', 'last_page']]]);
});

it('shows single cafe detail', function () {
    $cafe = Cafe::factory()->create();
    /** @var \Tests\TestCase $this */
    $this->getJson("/api/cafes/{$cafe->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $cafe->id);
});

it('admin can create a new cafe', function () {
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($admin)->postJson('/api/cafes', [
        'name' => 'Awake Coffee - Sudirman',
        'city' => 'Jakarta',
        'address' => 'Jl. Sudirman No. 1',
        'latitude' => -6.2,
        'longitude' => 106.8,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.is_active', true);

    $this->assertDatabaseHas('cafes', ['name' => 'Awake Coffee - Sudirman']);
});

it('cafe_manager cannot create a new cafe', function () {
    /** @var User $manager */
    $manager = User::factory()->create();
    $manager->assignRole('cafe_manager');

    /** @var \Tests\TestCase $this */
    $this->actingAs($manager)->postJson('/api/cafes', [
        'name' => 'Cafe Nakal',
        'city' => 'Jakarta',
        'address' => 'Jl. Test',
    ])->assertForbidden();
});

it('cafe_manager can update own cafe', function () {
    $cafe = Cafe::factory()->create();
    /** @var User $manager */
    $manager = User::factory()->create(['cafe_id' => $cafe->id]);
    $manager->assignRole('cafe_manager');

    /** @var \Tests\TestCase $this */
    $this->actingAs($manager)->putJson("/api/cafes/{$cafe->id}", [
        'name' => 'Nama Baru',
    ])->assertOk()
        ->assertJsonPath('data.name', 'Nama Baru');
});

it('cafe_manager cannot update other cafe', function () {
    $ownCafe = Cafe::factory()->create();
    $otherCafe = Cafe::factory()->create();
    /** @var User $manager */
    $manager = User::factory()->create(['cafe_id' => $ownCafe->id]);
    $manager->assignRole('cafe_manager');

    /** @var \Tests\TestCase $this */
    $this->actingAs($manager)->putJson("/api/cafes/{$otherCafe->id}", [
        'name' => 'Nama Baru',
    ])->assertForbidden();
});
it('includes open_status in cafe list response', function () {
    $cafe = Cafe::factory()->create();
    \App\Models\CafeOperatingHour::factory()->create([
        'cafe_id' => $cafe->id,
        'day_of_week' => \Carbon\Carbon::now()->dayOfWeek === 0 ? 6 : \Carbon\Carbon::now()->dayOfWeek - 1,
        'open_time' => '00:00:00',
        'close_time' => '23:59:59',
        'is_closed' => false,
    ]);
    /** @var \Tests\TestCase $this */
    $response = $this->getJson('/api/cafes');

    $response->assertOk()
        ->assertJsonPath('data.items.0.open_status', 'Buka');
});

it('includes open_status Tutup when cafe has no operating hours data', function () {
    $cafe = Cafe::factory()->create();
    /** @var \Tests\TestCase $this */
    $response = $this->getJson("/api/cafes/{$cafe->id}");

    $response->assertOk()
        ->assertJsonPath('data.open_status', 'Tutup');
});
