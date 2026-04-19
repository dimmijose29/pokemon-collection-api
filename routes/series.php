<?php

use App\Http\Controllers\SeriesController;
use Illuminate\Support\Facades\Route;

Route::prefix('series')->group(function () {
    Route::get('/', [SeriesController::class, 'index']);
    Route::get('/{series}', [SeriesController::class, 'show']);

    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/', [SeriesController::class, 'store']);
        Route::put('/{series}', [SeriesController::class, 'update']);
        Route::patch('/{series}', [SeriesController::class, 'update']);
        Route::put('/{series}/restore', [SeriesController::class, 'restore']);
        Route::delete('/{series}', [SeriesController::class, 'destroy']);
    });
});
