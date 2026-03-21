<?php

use App\Http\Controllers\ArtistController;
use Illuminate\Support\Facades\Route;

// Lettura pubblica

Route::prefix('artists')->group(function () {
    Route::get('/', [ArtistController::class, 'index']);
    Route::get('/{artist}', [ArtistController::class, 'show']);
    
    // Scrittura solo admin
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/', [ArtistController::class, 'store']);
        Route::put('/{artist}', [ArtistController::class, 'update']);
        Route::put('/{artist}/restore', [ArtistController::class, 'restore']);
        Route::patch('/{artist}', [ArtistController::class, 'update']);
        Route::delete('/{artist}', [ArtistController::class, 'destroy']);
    });
});