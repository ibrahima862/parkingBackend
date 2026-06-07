<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors; 

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        
       $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);

        $middleware->validateCsrfTokens(except: [
            'api/*', 
        ]);
        
        $middleware->redirectGuestsTo(fn () => response()->json([
            'success' => false,
            'message' => 'Non authentifié. Veuillez fournir un token valide.'
        ], 401));
        
        $middleware->statefulApi(); 
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();