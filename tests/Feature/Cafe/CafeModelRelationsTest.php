<?php

use App\Models\Cafe;
use App\Models\CafePhoto;
use App\Models\CafeOperatingHour;

it('cafe has many photos', function () {
    $cafe = Cafe::factory()->create();
    CafePhoto::factory()->count(2)->create(['cafe_id' => $cafe->id]);

    expect($cafe->photos)->toHaveCount(2);
    expect($cafe->photos->first())->toBeInstanceOf(CafePhoto::class);
});

it('cafe has many operating hours', function () {
    $cafe = Cafe::factory()->create();
    CafeOperatingHour::factory()->count(7)->create(['cafe_id' => $cafe->id]);

    expect($cafe->operatingHours)->toHaveCount(7);
});

it('cafe photo belongs to cafe', function () {
    $cafe = Cafe::factory()->create();
    $photo = CafePhoto::factory()->create(['cafe_id' => $cafe->id]);

    expect($photo->cafe->id)->toBe($cafe->id);
});

it('cafe is soft deleted, not hard deleted', function () {
    $cafe = Cafe::factory()->create();
    $cafe->delete();

    $deletedCafe = Cafe::withTrashed()->find($cafe->id);

    expect($deletedCafe)->not->toBeNull()
        ->and($deletedCafe->deleted_at)->not->toBeNull();
});
