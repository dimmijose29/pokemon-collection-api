<?php

use App\Http\Controllers\CurrencyController;
use Illuminate\Support\Facades\Route;

// Lettura pubblica

Route::prefix('currencies')->group(function () {
    Route::get('/', [CurrencyController::class, 'index']);
    Route::get('/{currency}', [CurrencyController::class, 'show']);
    
    // Scrittura solo admin
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/', [CurrencyController::class, 'store']);
        Route::put('/{currency}', [CurrencyController::class, 'update']);
        Route::put('/{currency}/restore', [CurrencyController::class, 'restore']);
        Route::patch('/{currency}', [CurrencyController::class, 'update']);
        Route::delete('/{currency}', [CurrencyController::class, 'destroy']);
    });
});