<?php

    return [
        'app' => [
            'name'  => env('APP_NAME', 'HRIS'),
            'env' => env('APP_ENV', 'production'),
            'debug' => (bool) env('APP_DEBUG', false),
            'url' => env('APP_URL', 'http://localhost'),
            'timezone' => env('APP_TIMEZONE', 'UTC'),
            'key' => env('APP_KEY', ''),
        ],

        'auth' => [
            // hours a personal access token stays valid after issue
            'token_ttl_hours' => (int) env('TOKEN_TTL_HOURS', 12),
        ],

        'cors' => [
            'allowed_origin' => env('CORS_ALLOWED_ORIGIN', '*'),
        ],
    ];
