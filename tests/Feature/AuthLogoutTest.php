<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed'); // pastikan role customer/cafe_manager/admin ada
});
it('revokes token on logout', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');
    $token = $user->createToken('test-token')->plainTextToken;

    $response = postJson('/api/logout', [], [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    expect($user->tokens()->count())->toBe(0);
});

it('rejects logout without token', function () {
    $response = postJson('/api/logout');

    $response->assertStatus(401);
});
