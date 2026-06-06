<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // On vérifie si l'utilisateur est connecté ET s'il est admin
        if ($request->user() && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Accès réservé aux administrateurs'], 403);
        }

        return $next($request);
    }
}