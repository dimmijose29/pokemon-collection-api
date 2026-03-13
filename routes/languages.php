<?php

use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

Route::prefix('languages')->group(function () {
    Route::get('/', [LanguageController::class, 'index']);
    Route::get('/{language}', [LanguageController::class, 'show']);
    
    // Scrittura solo admin
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/', [LanguageController::class, 'store']);
        Route::put('/{language}', [LanguageController::class, 'update']);
        Route::patch('/{language}', [LanguageController::class, 'update']);
        Route::put('/{language}/restore', [LanguageController::class, 'restore']);
        Route::delete('/{language}', [LanguageController::class, 'destroy']);
    });
});