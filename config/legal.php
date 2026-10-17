<?php

return [

    /*
    | Version of the legal documents the user accepts at sign-up. Bump it when
    | the texts change: users.terms_version shows which edition each user saw.
    */
    'version' => env('LEGAL_DOCUMENTS_VERSION', '2026-10-17'),

    'documents' => [
        'terms' => 'Пользовательское соглашение',
        'privacy' => 'Политика конфиденциальности',
        'offer' => 'Публичная оферта',
    ],

];
