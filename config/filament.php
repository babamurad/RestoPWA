<?php

// Only the broadcasting section is overridden; every other Filament option
// keeps the package default (vendor/filament/support/config/filament.php).
return [

    'broadcasting' => [

        // Realtime kanban for restaurateurs. Enabled when Reverb keys are set.
        'echo' => env('VITE_REVERB_APP_KEY') ? [
            'broadcaster' => 'reverb',
            'key' => env('VITE_REVERB_APP_KEY'),
            'wsHost' => env('VITE_REVERB_HOST'),
            'wsPort' => env('VITE_REVERB_PORT', 80),
            'wssPort' => env('VITE_REVERB_PORT', 443),
            'authEndpoint' => '/broadcasting/auth',
            'disableStats' => true,
            'enabledTransports' => ['ws', 'wss'],
            'forceTLS' => env('VITE_REVERB_SCHEME', 'https') === 'https',
        ] : null,

    ],

];
