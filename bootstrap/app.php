<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        using: function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/auth.php'))
                ->group(base_path('routes/rarities.php'))
                ->group(base_path('routes/artists.php'))
                ->group(base_path('routes/grading_companies.php'))
                ->group(base_path('routes/conditions.php'))
                ->group(base_path('routes/currencies.php'))
                ->group(base_path('routes/card_types.php'))
                ->group(base_path('routes/languages.php'))
                ->group(base_path('routes/series.php'))
                ->group(base_path('routes/sets.php'))
                ->group(base_path('routes/cards.php'))
                ->group(base_path('routes/users.php'))
                ->group(base_path('routes/stats.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->throttleApi();

        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdminCheck::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Messaggio in italiano per il rate limiting (login, 2FA, reset password...)
        $exceptions->render(function (ThrottleRequestsException $e) {
            $seconds = (int) ($e->getHeaders()['Retry-After'] ?? 60);

            return response()->json([
                'message' => "Troppi tentativi. Riprova tra {$seconds} secondi.",
            ], 429, $e->getHeaders());
        });
    })->create();
