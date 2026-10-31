<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // The Capacitor Android WebView serves the app from https://localhost;
    // iOS (when added later) uses capacitor://localhost.
    'allowed_origins' => [
        env('APP_URL', 'http://localhost'),
        'capacitor://localhost',
        'https://localhost',
        'http://localhost',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Mobile clients authenticate via Bearer token, not cookies, so credentials aren't needed.
    'supports_credentials' => false,
];
