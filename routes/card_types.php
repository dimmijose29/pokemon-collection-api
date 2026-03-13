<?php

use App\Http\Controllers\CardTypeController;
use Illuminate\Support\Facades\Route;

// Lettura pubblica

Route::prefix('card-types')->group(function () {
    Route::get('/', [CardTypeController::class, 'index']);
    Route::get('/{cardType}', [CardTypeController::class, 'show']);
    
    // Scrittura solo admin
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/', [CardTypeController::class, 'store']);
        Route::put('/{cardType}', [CardTypeController::class, 'update']);
        Route::put('/{cardType}/restore', [CardTypeController::class, 'restore']);
        Route::patch('/{cardType}', [CardTypeController::class, 'update']);
        Route::delete('/{cardType}', [CardTypeController::class, 'destroy']);
    });
});