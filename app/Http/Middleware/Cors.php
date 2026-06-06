<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        // Si c'est une requête OPTIONS (preflight), on retourne une réponse vide
        if ($request->getMethod() === "OPTIONS") {
            $response = response()->json([], 200);
        } else {
            $response = $next($request);
        }

        // En-têtes CORS
        $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:5173');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        return $response;
    }
}
    