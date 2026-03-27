<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::prefix('auth')->group(function () {

    Route::get('/up', function () {
        return response()->json(['status' => 'ok']);
    });

    // Registrazione
    Route::post('/register', [AuthController::class, 'register']);

    // Login
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/2fa/verify', [AuthController::class, 'verify2FA']);

    // Rotte protette
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/{user}/update-password', [AuthController::class, 'updatePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::prefix('2fa')->group(function () {
            Route::post('/enable', [AuthController::class, 'enable2FA']);
            Route::post('/confirm', [AuthController::class, 'confirm2FA']);
            Route::post('/disable', [AuthController::class, 'disable2FA']);
        });
    });
});
