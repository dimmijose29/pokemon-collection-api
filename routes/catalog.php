<?php

use App\Http\Controllers\ArtistController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CardTypeController;
use App\Http\Controllers\ConditionController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\GradingCompanyController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\RarityController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\SetController;
use Illuminate\Support\Facades\Route;

// Entità di catalogo: stesse rotte per tutte (vedi CatalogController).
// Lettura pubblica, scrittura solo admin.
$catalog = [
    'artists'           => ArtistController::class,
    'rarities'          => RarityController::class,
    'card-types'        => CardTypeController::class,
    'conditions'        => ConditionController::class,
    'currencies'        => CurrencyController::class,
    'grading-companies' => GradingCompanyController::class,
    'languages'         => LanguageController::class,
    'series'            => SeriesController::class,
    'sets'              => SetController::class,
    'cards'             => CardController::class,
];

foreach ($catalog as $prefix => $controller) {
    Route::prefix($prefix)->controller($controller)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');

        Route::middleware(['auth:sanctum', 'admin'])->group(function () {
            Route::post('/', 'store');
            Route::put('/{id}', 'update');
            Route::patch('/{id}', 'update');
            Route::put('/{id}/restore', 'restore');
            Route::delete('/{id}', 'destroy');
        });
    });
}
