<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class AuthService
{
    public function __construct(protected OtpService $otpService) {}

    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
            ]);
            $user->assignRole('customer');

            return $user;
        });
    }

    public function login(string $email, string $password): User
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $this->otpService->generate($user);

        return $user;
    }

    public function verifyOtp(string $email, string $code): NewAccessToken
    {
        $user = User::where('email', $email)->first();

        $result = $this->otpService->verify($user, $code);

        if (! $result['valid']) {
            $message = match ($result['reason']) {
                'expired' => 'OTP expired',
                'used' => 'OTP already used',
                default => 'Kode OTP salah',
            };

            throw ValidationException::withMessages([
                'code' => [$message],
            ]);
        }

        return $user->createToken('auth-token');
    }
}
