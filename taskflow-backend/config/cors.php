<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => env('APP_ENV') === 'production'
        ? [
            'http://localhost',
            'http://localhost:3000',
            'http://localhost:5173',
            'http://localhost:5174',
        ]
        : [
            'http://localhost',
            'http://localhost:3000',
            'http://localhost:5173',  // Frontend Vue en desarrollo
            'http://localhost:5174',  // Puerto alternativo
            'http://127.0.0.1:3000',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:5174',
            'http://172.16.10.25',    // IP del VPS
        ],

    'allowed_origins_patterns' => [
        'http://localhost:*', // Permitir cualquier puerto local en desarrollo
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];