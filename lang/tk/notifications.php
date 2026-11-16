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
    'receipt' => [
        'subject' => '№:number sargydyňyz kabul edildi',
        'greeting' => 'Sargydyňyz üçin sag boluň!',
        'intro' => '«:restaurant» restoranynda №:number sargyt resmileşdirildi. Sargydyň düzümi:',
        'delivery' => 'Eltip bermek: :amount',
        'total' => 'Jemi: :amount',
        'payment_cash' => 'Töleg sargyt alnanda nagt görnüşinde.',
        'action' => 'Sargytlarym',
        'salutation' => 'Işdäňiz açyk bolsun!',
    ],
];
