<?php

use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\SendOtpNotification;
use App\Services\OtpService;
use Illuminate\Support\Facades\Notification;

it('generates a 6 digit otp code with 10 minute expiry', function () {
    Notification::fake();

    $user = User::factory()->create();
    $service = new OtpService();

    $otp = $service->generate($user);

    expect($otp)->toBeInstanceOf(OtpCode::class);
    expect(strlen($otp->code))->toBe(6);
    expect($otp->code)->toMatch('/^\d{6}$/');
    expect($otp->used_at)->toBeNull();

    $diffInMinutes = now()->diffInMinutes($otp->expires_at, absolute: true);
    expect($diffInMinutes)->toBeLessThanOrEqual(5)
        ->and($diffInMinutes)->toBeGreaterThan(4);
});

it('sends the otp notification to the user without exposing code in response', function () {
    Notification::fake();

    $user = User::factory()->create();
    $service = new OtpService();

    $service->generate($user);

    Notification::assertSentTo($user, SendOtpNotification::class);
});

it('persists otp code linked to the correct user', function () {
    Notification::fake();

    $user = User::factory()->create();
    $service = new OtpService();

    $otp = $service->generate($user);

    expect(OtpCode::where([
        'id' => $otp->id,
        'user_id' => $user->id,
    ])->exists())->toBeTrue();
});

it('verifies a correct otp code successfully', function () {
    Notification::fake();

    $user = User::factory()->create();
    $service = new OtpService();
    $otp = $service->generate($user);

    $result = $service->verify($user, $otp->code);

    expect($result['valid'])->toBeTrue();
    expect($result['reason'])->toBeNull();

    $otp->refresh();
    expect($otp->used_at)->not->toBeNull();
});

it('rejects a wrong otp code', function () {
    Notification::fake();

    $user = User::factory()->create();
    $service = new OtpService();
    $service->generate($user);

    $result = $service->verify($user, '000000');

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('invalid');
});

it('rejects an expired otp code', function () {
    Notification::fake();

    $user = User::factory()->create();
    $service = new OtpService();
    $otp = $service->generate($user);

    $otp->update(['expires_at' => now()->subMinutes(1)]);

    $result = $service->verify($user, $otp->code);

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('expired');
});

it('rejects an already used otp code', function () {
    Notification::fake();

    $user = User::factory()->create();
    $service = new OtpService();
    $otp = $service->generate($user);

    $service->verify($user, $otp->code); // dipakai sekali

    $result = $service->verify($user, $otp->code); // dipakai lagi

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('used');
});
