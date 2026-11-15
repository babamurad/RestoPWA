<?php

return [
    'order' => [
        'confirmed' => [
            'title' => 'Sargydyňyz kabul edildi',
            'body' => 'Restoran sargydyňyzy kabul etdi',
        ],
        'preparing' => [
            'title' => 'Sargydyňyz taýýarlanyp başlandy',
            'body' => 'Restoran sargydyňyzy taýýarlap başlady',
        ],
        'delivering' => [
            'title' => 'Sargydyňyz ýolda',
            'body' => 'Kurýer size tarap ýola çykdy',
        ],
        'delivered' => [
            'title' => 'Sargydyňyz eltildi',
            'body' => 'Sargydyňyz eltip berildi',
        ],
        'cancelled' => [
            'title' => 'Sargydyňyz ýatyryldy',
            'body' => 'Sargydyňyz ýatyryldy.',
            'reason' => 'Sebäbi: :reason',
        ],
        'default' => [
            'title' => 'Sargydyň ýagdaýy üýtgedi',
            'body' => '',
        ],
    ],
    'cancel_reasons' => [
        'changed_mind' => 'müşderi tarapyndan ýatyryldy',
        'out_of_stock' => 'tagam häzir ýok',
        'too_busy' => 'restoran sargydy taýýarlap ýetişenok',
        'closing' => 'restoran ýapylýar',
        'cannot_deliver' => 'siziň salgyňyza häzir eltip bolmaýar',
        'other' => 'restoran sargydy ýerine ýetirip bilmeýär',
    ],
];
