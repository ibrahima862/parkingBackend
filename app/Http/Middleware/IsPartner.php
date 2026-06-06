<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsPartner
{
    public function handle(Request $request, Closure $next)
    {
        
        if (auth()->check() && auth()->user()->role === 'proprietaireparking') {
            return $next($request);
        }

        return response()->json(['message' => 'Accès réservé aux partenaires.'], 403);
    }
}