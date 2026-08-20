<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed');
});

it('formats validation error response with success:false', function () {
    $response = postJson('/api/register', []); // kosong, pasti gagal validasi

    $response->assertStatus(422)
        ->assertJsonStructure(['success', 'errors', 'message'])
        ->assertJson(['success' => false]);
});

it('formats unauthenticated error response with success:false', function () {
    $response = getJson('/api/me');

    $response->assertStatus(401)
        ->assertJsonStructure(['success', 'errors', 'message'])
        ->assertJson(['success' => false]);
});

it('formats forbidden error response with success:false', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $token = $customer->createToken('test-token')->plainTextToken;

    $response = getJson('/api/admin/ping', [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertStatus(403)
        ->assertJsonStructure(['success', 'errors', 'message'])
        ->assertJson(['success' => false]);
});

it('formats rate limit error response with success:false', function () {
    User::factory()->create(['email' => 'budi@example.com', 'password' => 'password123']);

    for ($i = 0; $i < 5; $i++) {
        postJson('/api/login', ['email' => 'budi@example.com', 'password' => 'wrong']);
    }

    $response = postJson('/api/login', ['email' => 'budi@example.com', 'password' => 'wrong']);

    $response->assertStatus(429)
        ->assertJsonStructure(['success', 'errors', 'message'])
        ->assertJson(['success' => false]);
});
