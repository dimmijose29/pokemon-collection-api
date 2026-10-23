<?php

use Illuminate\Support\Facades\Route;

// Progetto solo API: la root risponde con un JSON minimo invece della pagina di benvenuto
Route::get('/', function () {
    return response()->json([
        'name'   => config('app.name'),
        'status' => 'ok',
        'api'    => url('/api'),
    ]);
});
