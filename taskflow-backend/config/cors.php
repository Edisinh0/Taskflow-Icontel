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
        ? ['*']  // En producción, permitir todos los orígenes (o especifica tu IP)
        : [
            'http://localhost:5173',  // Frontend Vue en desarrollo
            'http://127.0.0.1:5173',
            'http://172.16.10.25',    // IP del VPS
        ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];