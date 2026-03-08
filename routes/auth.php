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

    // Rotte protette
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        
        // Attivazione / verifica 2FA (solo utente loggato)
        Route::post('/2fa/enable', [AuthController::class, 'enable2FA']);
        Route::post('/2fa/confirm', [AuthController::class, 'confirm2FA']);
        Route::post('/2fa/disable', [AuthController::class, 'disable2FA']);
        Route::post('/2fa/verify', [AuthController::class, 'verify2FA']);
    });
});