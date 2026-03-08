<?php

use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

Route::apiResource('languages', LanguageController::class);
