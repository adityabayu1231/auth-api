<?php

use App\Enums\CafeOpenStatus;
use App\Models\Cafe;
use App\Models\CafeOperatingHour;
use App\Services\CafeStatusService;
use Carbon\Carbon;

it('returns Open when current time is within operating hours', function () {
    $service = new CafeStatusService();
    $cafe = Cafe::factory()->create();
    // Senin = project day 0. Carbon: Senin, 14 Sep 2026 (pastikan hari Senin).
    $monday = Carbon::parse('2026-09-14 14:00:00'); // 2026-09-14 adalah hari Senin
    expect($monday->dayOfWeek)->toBe(Carbon::MONDAY);

    CafeOperatingHour::factory()->create([
        'cafe_id' => $cafe->id,
        'day_of_week' => 0, // Senin, sesuai konvensi project
        'open_time' => '08:00:00',
        'close_time' => '20:00:00',
        'is_closed' => false,
    ]);
    $cafe->load('operatingHours');

    $status = $service->getOpenStatus($cafe, $monday);

    expect($status)->toBe(CafeOpenStatus::Open);
});

it('returns Closed when current time is outside operating hours', function () {
    $service = new CafeStatusService();
    $cafe = Cafe::factory()->create();
    $mondayNight = Carbon::parse('2026-09-14 22:00:00');

    CafeOperatingHour::factory()->create([
        'cafe_id' => $cafe->id,
        'day_of_week' => 0,
        'open_time' => '08:00:00',
        'close_time' => '20:00:00',
        'is_closed' => false,
    ]);
    $cafe->load('operatingHours');

    expect($service->getOpenStatus($cafe, $mondayNight))->toBe(CafeOpenStatus::Closed);
});

it('returns Closed when is_closed is true regardless of time', function () {
    $service = new CafeStatusService();
    $cafe = Cafe::factory()->create();
    $sunday = Carbon::parse('2026-09-13 14:00:00'); // Minggu
    expect($sunday->dayOfWeek)->toBe(Carbon::SUNDAY);

    CafeOperatingHour::factory()->create([
        'cafe_id' => $cafe->id,
        'day_of_week' => 6, // Minggu, sesuai konvensi project
        'open_time' => '08:00:00',
        'close_time' => '20:00:00',
        'is_closed' => true,
    ]);
    $cafe->load('operatingHours');

    expect($service->getOpenStatus($cafe, $sunday))->toBe(CafeOpenStatus::Closed);
});

it('returns Closed when there is no operating hour data at all for that day', function () {
    $service = new CafeStatusService();
    $cafe = Cafe::factory()->create();
    $tuesday = Carbon::parse('2026-09-15 14:00:00');
    // Sengaja tidak buat CafeOperatingHour sama sekali
    $cafe->load('operatingHours');

    expect($service->getOpenStatus($cafe, $tuesday))->toBe(CafeOpenStatus::Closed);
});

it('maps Carbon Sunday (0) to project day_of_week 6 correctly', function () {
    $service = new CafeStatusService();
    $cafe = Cafe::factory()->create();
    $sunday = Carbon::parse('2026-09-13 10:00:00');
    expect($sunday->dayOfWeek)->toBe(0); // Carbon native: Minggu = 0

    CafeOperatingHour::factory()->create([
        'cafe_id' => $cafe->id,
        'day_of_week' => 6, // harus dicari di project-day 6, BUKAN 0
        'open_time' => '08:00:00',
        'close_time' => '20:00:00',
        'is_closed' => false,
    ]);
    $cafe->load('operatingHours');

    expect($service->getOpenStatus($cafe, $sunday))->toBe(CafeOpenStatus::Open);
});

it('maps Carbon Monday (1) to project day_of_week 0 correctly', function () {
    $service = new CafeStatusService();
    $cafe = Cafe::factory()->create();
    $monday = Carbon::parse('2026-09-14 10:00:00');
    expect($monday->dayOfWeek)->toBe(1); // Carbon native: Senin = 1

    CafeOperatingHour::factory()->create([
        'cafe_id' => $cafe->id,
        'day_of_week' => 0, // harus dicari di project-day 0, BUKAN 1
        'open_time' => '08:00:00',
        'close_time' => '20:00:00',
        'is_closed' => false,
    ]);
    $cafe->load('operatingHours');

    expect($service->getOpenStatus($cafe, $monday))->toBe(CafeOpenStatus::Open);
});
