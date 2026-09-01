<?php

use App\Http\Controllers\Api\Admin\TopupRequestController;
use App\Http\Controllers\Api\AdminPingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CafeController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (tidak butuh login)
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:5,1');

Route::prefix('cafes')->group(function () {
    Route::get('/', [CafeController::class, 'index']);
    Route::get('/{cafe}', [CafeController::class, 'show']);
    Route::get('/{cafe}/products', [ProductController::class, 'indexByCafe']);
});

Route::get('/products/{product}', [ProductController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes (butuh login, role-check per grup di bawah)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // --- Akun sendiri, semua role ---
    Route::get('/user', fn(Request $request) => $request->user());
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- Wallet: setiap user yang login lihat wallet miliknya sendiri ---
    Route::prefix('wallet')->group(function () {
        Route::get('/', [WalletController::class, 'show']);
        Route::post('/topup-request', [WalletController::class, 'storeTopupRequest']);
    });

    // --- Cafe management: admin & cafe_manager ---
    Route::middleware('role:admin|cafe_manager')->prefix('cafes')->group(function () {
        Route::put('/{cafe}', [CafeController::class, 'update']);
        Route::post('/{cafe}/photos', [CafeController::class, 'uploadPhoto']);
        Route::put('/{cafe}/operating-hours', [CafeController::class, 'updateOperatingHours']);
    });
    Route::middleware('role:admin')->post('/cafes', [CafeController::class, 'store']);

    // --- Product & option management: admin & cafe_manager ---
    Route::middleware('role:admin|cafe_manager')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::post('/products/{product}/options', [ProductController::class, 'addOptions']);
        Route::put('/product-options/{productOption}', [ProductController::class, 'updateOption']);
    });

    // --- Orders: akses berbeda per aksi, dikelompokkan per role kombinasi ---
    Route::prefix('orders')->group(function () {
        Route::middleware('role:customer')->group(function () {
            Route::post('/', [OrderController::class, 'store']);
            Route::get('/', [OrderController::class, 'index']);
        });

        Route::middleware('role:customer|cafe_manager|admin')
            ->get('/{order}', [OrderController::class, 'show']);

        Route::middleware('role:customer|admin')
            ->patch('/{order}/cancel', [OrderController::class, 'cancel']);

        Route::middleware('role:admin|cafe_manager')
            ->patch('/{order}/status', [OrderController::class, 'updateStatus']);
    });

    // --- Admin only ---
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/ping', AdminPingController::class);

        Route::prefix('admin/topup-requests')->group(function () {
            Route::patch('/{topupRequest}/approve', [TopupRequestController::class, 'approve']);
            Route::patch('/{topupRequest}/reject', [TopupRequestController::class, 'reject']);
        });
    });
});
