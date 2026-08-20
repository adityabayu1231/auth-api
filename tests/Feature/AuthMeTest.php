<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed');
});

it('returns current user data when authenticated', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');
    $token = $user->createToken('test-token')->plainTextToken;

    $response = getJson('/api/me', [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => 'customer',
            ],
        ]);
});

it('rejects unauthenticated request', function () {
    $response = getJson('/api/me');

    $response->assertStatus(401);
});
