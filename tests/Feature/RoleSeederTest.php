<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

it('seeds the three base roles', function () {
    Artisan::call('db:seed');

    expect(Role::pluck('name')->sort()->values()->all())
        ->toBe(['admin', 'cafe_manager', 'customer']);
});

it('users table has phone, cafe_id, and soft delete columns', function () {
    expect(\Schema::hasColumns('users', ['phone', 'cafe_id', 'deleted_at']))->toBeTrue();
});

it('otp_codes table exists with expected columns', function () {
    expect(\Schema::hasColumns('otp_codes', ['user_id', 'code', 'expires_at', 'used_at']))->toBeTrue();
});
