<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleCorsHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Ajout des headers pour autoriser le CORS
        $response->headers->set('Access-Control-Allow-Origin', 'https://parking-frontend-l1swe4jb9-ibrahima-s-projects4.vercel.app');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-XSRF-TOKEN');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        return $response;
    }
}