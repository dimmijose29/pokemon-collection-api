<?php

use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])->apiResource('languages', LanguageController::class);
