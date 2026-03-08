<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdminCheck
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->is_admin) {
            return response()->json([
                'message' => 'Accesso non autorizzato. Richiesti privilegi di amministratore.'
            ], 403);
        }

        return $next($request);
    }
}
