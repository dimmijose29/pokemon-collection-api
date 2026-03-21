<?php

use App\Http\Controllers\GradingCompanyController;
use Illuminate\Support\Facades\Route;

// Lettura pubblica

Route::prefix('grading-companies')->group(function () {
    Route::get('/', [GradingCompanyController::class, 'index']);
    Route::get('/{gradingCompany}', [GradingCompanyController::class, 'show']);
    
    // Scrittura solo admin
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/', [GradingCompanyController::class, 'store']);
        Route::put('/{gradingCompany}', [GradingCompanyController::class, 'update']);
        Route::put('/{gradingCompany}/restore', [GradingCompanyController::class, 'restore']);
        Route::patch('/{gradingCompany}', [GradingCompanyController::class, 'update']);
        Route::delete('/{gradingCompany}', [GradingCompanyController::class, 'destroy']);
    });
});