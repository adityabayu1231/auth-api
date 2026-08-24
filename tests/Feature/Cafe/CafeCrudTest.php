<?php

use App\Models\Cafe;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\CafeOperatingHour;

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

it('cafe_manager can upload photo for own cafe', function () {
    Storage::fake('public');

    $cafe = Cafe::factory()->create();
    /** @var User $manager */
    $manager = User::factory()->create(['cafe_id' => $cafe->id]);
    $manager->assignRole('cafe_manager');

    $photo = UploadedFile::fake()->image('cafe.jpg', 800, 600)->size(500); // 500KB
    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($manager)->postJson("/api/cafes/{$cafe->id}/photos", [
        'photo' => $photo,
        'sort_order' => 1,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    /** @var \Tests\TestCase $this */
    $this->assertDatabaseHas('cafe_photos', ['cafe_id' => $cafe->id, 'sort_order' => 1]);

    $storedPath = $response->json('data.photo_path');
    Storage::disk('public')->assertExists($storedPath);
});

it('rejects photo upload exceeding size limit', function () {
    Storage::fake('public');

    $cafe = Cafe::factory()->create();
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $photo = UploadedFile::fake()->image('big.jpg')->size(3000); // 3MB > limit 2MB

    /** @var \Tests\TestCase $this */
    $this->actingAs($admin)->postJson("/api/cafes/{$cafe->id}/photos", [
        'photo' => $photo,
    ])->assertStatus(422);
});

it('cafe_manager cannot upload photo for other cafe', function () {
    Storage::fake('public');

    $ownCafe = Cafe::factory()->create();
    $otherCafe = Cafe::factory()->create();
    /** @var User $manager */
    $manager = User::factory()->create(['cafe_id' => $ownCafe->id]);
    $manager->assignRole('cafe_manager');

    $photo = UploadedFile::fake()->image('cafe.jpg')->size(500);
    /** @var \Tests\TestCase $this */
    $this->actingAs($manager)->postJson("/api/cafes/{$otherCafe->id}/photos", [
        'photo' => $photo,
    ])->assertForbidden();
});

it('admin can update all 7 days of operating hours at once', function () {
    $cafe = Cafe::factory()->create();
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $hours = [];
    for ($i = 0; $i < 7; $i++) {
        $hours[] = [
            'day_of_week' => $i,
            'open_time' => '08:00',
            'close_time' => '20:00',
            'is_closed' => false,
        ];
    }
    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($admin)->putJson("/api/cafes/{$cafe->id}/operating-hours", [
        'hours' => $hours,
    ]);

    $response->assertOk();
    expect(CafeOperatingHour::where('cafe_id', $cafe->id)->count())->toBe(7);
});

it('rejects operating hours where close_time is before open_time', function () {
    $cafe = Cafe::factory()->create();
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $hours = [];
    for ($i = 0; $i < 7; $i++) {
        $hours[] = [
            'day_of_week' => $i,
            'open_time' => '20:00',
            'close_time' => '08:00', // salah, close < open
            'is_closed' => false,
        ];
    }
    /** @var \Tests\TestCase $this */
    $this->actingAs($admin)->putJson("/api/cafes/{$cafe->id}/operating-hours", [
        'hours' => $hours,
    ])->assertStatus(422);
});

it('cafe_manager cannot update operating hours for other cafe', function () {
    $ownCafe = Cafe::factory()->create();
    $otherCafe = Cafe::factory()->create();
    /** @var User $manager */
    $manager = User::factory()->create(['cafe_id' => $ownCafe->id]);
    $manager->assignRole('cafe_manager');

    $hours = [];
    for ($i = 0; $i < 7; $i++) {
        $hours[] = ['day_of_week' => $i, 'open_time' => '08:00', 'close_time' => '20:00', 'is_closed' => false];
    }
    /** @var \Tests\TestCase $this */
    $this->actingAs($manager)->putJson("/api/cafes/{$otherCafe->id}/operating-hours", [
        'hours' => $hours,
    ])->assertForbidden();
});
