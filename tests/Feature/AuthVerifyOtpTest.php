<?php

use App\Models\User;
use App\Services\OtpService;
use function Pest\Laravel\postJson;

it('issues sanctum token on correct otp', function () {
    $user = User::factory()->create();
    $otp = (new OtpService())->generate($user);

    // ambil code langsung dari DB karena tidak dikirim di response (spec §3, §4)
    $response = postJson('/api/verify-otp', [
        'email' => $user->email,
        'code' => $otp->code,
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure(['data' => ['token']]);
});

it('rejects expired otp with specific message', function () {
    $user = User::factory()->create();
    $otp = (new OtpService())->generate($user);
    $otp->update(['expires_at' => now()->subMinute()]);

    $response = postJson('/api/verify-otp', [
        'email' => $user->email,
        'code' => $otp->code,
    ]);

    $response->assertStatus(422)
        ->assertJsonFragment(['code' => ['OTP expired']]);
});

it('rejects already used otp with specific message', function () {
    $user = User::factory()->create();
    $otp = (new OtpService())->generate($user);
    $otp->update(['used_at' => now()]);

    $response = postJson('/api/verify-otp', [
        'email' => $user->email,
        'code' => $otp->code,
    ]);

    $response->assertStatus(422)
        ->assertJsonFragment(['code' => ['OTP already used']]);
});
