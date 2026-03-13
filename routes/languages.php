<?php

use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

// Rotte di lettura pubbliche (index e show)
// Nessun middleware auth/admin
Route::get('/languages', [LanguageController::class, 'index']);
Route::get('/languages/{language}', [LanguageController::class, 'show']);

// Rotte di scrittura protette (store, update, destroy)
// Solo admin autenticati
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/languages', [LanguageController::class, 'store']);
    Route::put('/languages/{language}', [LanguageController::class, 'update']);
    Route::patch('/languages/{language}', [LanguageController::class, 'update']);
    Route::delete('/languages/{language}', [LanguageController::class, 'destroy']);
});