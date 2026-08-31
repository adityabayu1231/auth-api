<?php

use App\Http\Controllers\Api\AdminPingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CafeController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:6,1');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
    ->middleware('throttle:5,1');

Route::get('/cafes', [CafeController::class, 'index']);
Route::get('/cafes/{cafe}', [CafeController::class, 'show']);
Route::get('/cafes/{cafe}/products', [ProductController::class, 'indexByCafe']);
Route::get('/products/{product}', [ProductController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/ping', AdminPingController::class);
        Route::post('/cafes', [CafeController::class, 'store']);
    });

    Route::middleware('role:admin|cafe_manager')->group(function () {
        Route::put('/cafes/{cafe}', [CafeController::class, 'update']);
        Route::post('/cafes/{cafe}/photos', [CafeController::class, 'uploadPhoto']);
        Route::put('/cafes/{cafe}/operating-hours', [CafeController::class, 'updateOperatingHours']);

        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::post('/products/{product}/options', [ProductController::class, 'addOptions']);
        Route::put('/product-options/{productOption}', [ProductController::class, 'updateOption']);
    });

    Route::middleware('role:customer')->group(function () {
        Route::post('/orders', [OrderController::class, 'store']);
    });
});
