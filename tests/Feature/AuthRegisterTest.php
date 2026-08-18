<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed'); // pastikan role customer/cafe_manager/admin ada
});

it('registers a new user with customer role', function () {
    $response = postJson('/api/register', [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
        ]);

    $user = User::where('email', 'budi@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->hasRole('customer'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Hash::check('password123', $user->password))->toBeTrue();
});

it('rejects registration with duplicate email', function () {
    User::factory()->create(['email' => 'budi@example.com']);

    $response = postJson('/api/register', [
        'name' => 'Budi Lain',
        'email' => 'budi@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});