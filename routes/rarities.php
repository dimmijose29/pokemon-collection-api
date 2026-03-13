<?php

use App\Http\Controllers\RarityController;
use Illuminate\Support\Facades\Route;

// Lettura pubblica
Route::get('/rarities', [RarityController::class, 'index']);
Route::get('/rarities/{rarity}', [RarityController::class, 'show']);

// Scrittura solo admin
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/rarities', [RarityController::class, 'store']);
    Route::put('/rarities/{rarity}', [RarityController::class, 'update']);
    Route::patch('/rarities/{rarity}', [RarityController::class, 'update']);
    Route::delete('/rarities/{rarity}', [RarityController::class, 'destroy']);
});