<?php

use App\Models\User;
use App\Notifications\SendOtpNotification;
use Illuminate\Support\Facades\Notification;
use function Pest\Laravel\postJson;

it('sends otp when login credentials are correct', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'budi@example.com',
        'password' => 'password123',
    ]);

    $response = postJson('/api/login', [
        'email' => 'budi@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    Notification::assertSentTo($user, SendOtpNotification::class);
});

it('rejects login with wrong password', function () {
    User::factory()->create([
        'email' => 'budi@example.com',
        'password' => 'password123',
    ]);

    $response = postJson('/api/login', [
        'email' => 'budi@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('returns 429 after 6th login attempt within a minute', function () {
    User::factory()->create([
        'email' => 'budi@example.com',
        'password' => 'password123',
    ]);

    for ($i = 0; $i < 5; $i++) {
        postJson('/api/login', [
            'email' => 'budi@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    $response = postJson('/api/login', [
        'email' => 'budi@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(429);
});
