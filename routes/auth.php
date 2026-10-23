<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::prefix('auth')->group(function () {

    Route::get('/up', function () {
        return response()->json(['status' => 'ok']);
    });

    // Registrazione
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');

    // Login
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/2fa/verify', [AuthController::class, 'verify2FA'])->middleware('throttle:two-factor');

    // Recupero password
    Route::middleware('throttle:password-reset')->group(function () {
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    // Rotte protette
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'updatePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::prefix('2fa')->group(function () {
            Route::post('/enable', [AuthController::class, 'enable2FA']);
            Route::post('/confirm', [AuthController::class, 'confirm2FA']);
            Route::post('/disable', [AuthController::class, 'disable2FA']);
            Route::post('/cancel', [AuthController::class, 'cancelSetup2FA']);
        });
    });
});
