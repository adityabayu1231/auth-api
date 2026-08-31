<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors(),
                    'message' => 'Data yang dikirim tidak valid.',
                ], $e->status);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'errors' => [],
                    'message' => 'Anda belum login atau token tidak valid.',
                ], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'errors' => [],
                    'message' => 'Anda tidak memiliki akses untuk aksi ini.',
                ], 403);
            }
        });

        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'errors' => [],
                    'message' => 'Anda tidak memiliki akses untuk aksi ini.',
                ], 403);
            }
        });

        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'errors' => [],
                    'message' => 'Terlalu banyak percobaan. Silakan coba lagi nanti.',
                ], 429);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'errors' => [],
                    'message' => 'Endpoint tidak ditemukan.',
                ], 404);
            }
        });

        $exceptions->render(function (\App\Exceptions\InsufficientBalanceException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'errors' => [],
                    'message' => $e->getMessage(),
                ], 422);
            }
        });

        $exceptions->render(function (\App\Exceptions\ProductUnavailableException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'errors' => [],
                    'message' => $e->getMessage(),
                ], 422);
            }
        });

        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*') && ! config('app.debug')) {
                return response()->json([
                    'success' => false,
                    'errors' => [],
                    'message' => 'Terjadi kesalahan pada server.',
                ], 500);
            }
        });
    })->create();
