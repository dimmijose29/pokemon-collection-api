<?php

use App\Http\Controllers\CardController;
use Illuminate\Support\Facades\Route;

Route::prefix('cards')->group(function () {
    // Lettura pubblica
    Route::get('/', [CardController::class, 'index']);
    Route::get('/{card}', [CardController::class, 'show']);

    // Scrittura solo admin
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/', [CardController::class, 'store']);
        Route::put('/{card}', [CardController::class, 'update']);
        Route::put('/{card}/restore', [CardController::class, 'restore']);
        Route::patch('/{card}', [CardController::class, 'update']);
        Route::delete('/{card}', [CardController::class, 'destroy']);
    });
});
