<?php

return [
    'validation' => [
        'phone' => [
            'required' => 'Enter your phone number',
            'missing_plus' => 'Phone number must start with +',
            'strict_format' => 'Format: :example (:country)',
            'e164_format' => 'Enter an international format number, e.g. +99312345678',
            'e164_helper' => 'International format: +<country code><number>',
            'strict_helper' => 'Format for :country',
            'not_allowed' => 'Phone numbers from this country are not supported',
            'config_error' => 'Configuration error. Please contact support.',
        ],
        'name' => [
            'required' => 'Enter your name',
        ],
        'comment' => [
            'too_long' => 'Comment is too long (max :max characters)',
        ],
    ],
    'summary' => [
        'contacts' => 'Contacts',
        'name_label' => 'Your name',
        'phone_label' => 'Phone number',
        'phone_placeholder' => '+99312345678',
        'comment_label' => 'Order comment',
        'comment_placeholder' => 'e.g. do not ring, baby is sleeping',
    ],
];
