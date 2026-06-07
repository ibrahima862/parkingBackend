<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Autoriser systématiquement la requête OPTIONS (CORS Preflight)
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        // 2. Vérifier l'authentification et le rôle
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Accès réservé aux administrateurs'], 403);
        }

        return $next($request);
    }
}