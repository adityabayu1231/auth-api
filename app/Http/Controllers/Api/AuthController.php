<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'message' => 'Registrasi berhasil.',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $this->authService->login(
            $request->validated('email'),
            $request->validated('password'),
        );

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'OTP telah dikirim ke email Anda.',
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $token = $this->authService->verifyOtp(
            $request->validated('email'),
            $request->validated('code'),
        );

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token->plainTextToken,
            ],
            'message' => 'Login berhasil.',
        ]);
    }

    public function logout(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Logout berhasil.',
        ]);
    }
}
