<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => 
    [
        'http://localhost:5173', 
        'https://indissolubly-unmediating-tressa.ngrok-free.dev',
        'http://127.0.0.1:8000',
        'http://192.168.1.2:8000',
        'https://parking-frontend-3ofn5wpc0-ibrahima-s-projects4.vercel.app',
        'http://192.168.1.2:5173',
        'https://parkingbackend-4wcy.onrender.com'
        ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];