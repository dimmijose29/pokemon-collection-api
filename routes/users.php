<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{user}', [UserController::class, 'show']);
    Route::patch('/{user}', [UserController::class, 'update']);
    Route::put('/{user}', [UserController::class, 'update']);
    // POST usato per upload avatar (multipart non supportato nativamente da PATCH/PUT in PHP)
    Route::post('/{user}', [UserController::class, 'update']);
    Route::put('/{user}/restore', [UserController::class, 'restore']);
    Route::delete('/{user}', [UserController::class, 'destroy']);
});
