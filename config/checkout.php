<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Phone Validation Mode
    |--------------------------------------------------------------------------
    |
    | Supported: "strict_region", "e164"
    |
    | strict_region: Validates phone against a specific country pattern.
    | e164: Validates against international E.164 format (+[1-9]\d{7,14}).
    |
    */
    'phone' => [
        'mode' => env('CHECKOUT_PHONE_MODE', 'strict_region'),

        // Default country code for strict_region mode (e.g., '993' for Turkmenistan)
        'default_country' => env('CHECKOUT_PHONE_DEFAULT_COUNTRY', '993'),

        // Optional whitelist of allowed country codes (empty = all allowed in e164 mode)
        'allowed_countries' => [
            // '993', // TM
            // '7',   // RU/KZ
            // '1',   // US/CA
        ],

        // Whether to require customer name
        'require_name' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Comment Limits
    |--------------------------------------------------------------------------
    */
    'max_comment_length' => env('CHECKOUT_MAX_COMMENT_LENGTH', 500),

    /*
    |--------------------------------------------------------------------------
    | Default Geolocation on Checkout
    |--------------------------------------------------------------------------
    |
    | When enabled, the address step in checkout will attempt to center the map
    | on the user's current geolocation automatically.
    |
    */
    'default_geolocate_enabled' => env('CHECKOUT_DEFAULT_GEOLOCATE_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Regional Phone Patterns (for strict_region mode)
    |--------------------------------------------------------------------------
    | Key: country code (without +), Value: regex pattern for the national part
    |
    */
    'phone_patterns' => [
        '993' => [
            'pattern' => '/^\+993\d{8}$/',
            'example' => '+99361234567',
            'label' => 'Туркменистан',
        ],
        '7' => [
            'pattern' => '/^\+7\d{10}$/',
            'example' => '+79123456789',
            'label' => 'Россия/Казахстан',
        ],
        '1' => [
            'pattern' => '/^\+1\d{10}$/',
            'example' => '+12125551234',
            'label' => 'США/Канада',
        ],
    ],
];
