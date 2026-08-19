<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed'); // pastikan role customer/cafe_manager/admin ada
});

it('allows admin role to access admin-only endpoint', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $token = $admin->createToken('test-token')->plainTextToken;

    $response = getJson('/api/admin/ping', [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);
});

it('rejects customer role from admin-only endpoint with 403', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $token = $customer->createToken('test-token')->plainTextToken;

    $response = getJson('/api/admin/ping', [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertStatus(403);
});
