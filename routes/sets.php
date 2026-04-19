<?php

use App\Http\Controllers\SetController;
use Illuminate\Support\Facades\Route;

Route::prefix('sets')->group(function () {
    // Lettura pubblica
    Route::get('/', [SetController::class, 'index']);
    Route::get('/{set}', [SetController::class, 'show']);

    // Scrittura solo admin
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/', [SetController::class, 'store']);
        Route::put('/{set}', [SetController::class, 'update']);
        Route::put('/{set}/restore', [SetController::class, 'restore']);
        Route::patch('/{set}', [SetController::class, 'update']);
        Route::delete('/{set}', [SetController::class, 'destroy']);
    });
});
