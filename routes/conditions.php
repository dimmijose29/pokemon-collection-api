<?php

use App\Http\Controllers\ConditionController;
use Illuminate\Support\Facades\Route;

// Lettura pubblica

Route::prefix('conditions')->group(function () {
    Route::get('/', [ConditionController::class, 'index']);
    Route::get('/{condition}', [ConditionController::class, 'show']);
    
    // Scrittura solo admin
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/', [ConditionController::class, 'store']);
        Route::put('/{condition}', [ConditionController::class, 'update']);
        Route::put('/{condition}/restore', [ConditionController::class, 'restore']);
        Route::patch('/{condition}', [ConditionController::class, 'update']);
        Route::delete('/{condition}', [ConditionController::class, 'destroy']);
    });
});