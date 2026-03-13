<?php

use App\Http\Controllers\RarityController;
use Illuminate\Support\Facades\Route;

Route::prefix('rarities')->group(function () {
    Route::get('/', [RarityController::class, 'index']);
    Route::get('/{rarity}', [RarityController::class, 'show']);
    
    // Scrittura solo admin
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/', [RarityController::class, 'store']);
        Route::put('/{rarity}', [RarityController::class, 'update']);
        Route::put('/{rarity}/restore', [RarityController::class, 'restore']);
        Route::patch('/{rarity}', [RarityController::class, 'update']);
        Route::delete('/{rarity}', [RarityController::class, 'destroy']);
    });
});
