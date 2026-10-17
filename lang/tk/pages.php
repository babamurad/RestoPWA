<?php

// Customer-facing server-rendered pages (order tracking, success, offline, PWA installer)
return [
    'tracking' => [
        'title' => 'Sargydy yzarlamak',
        'order_number' => 'Sargyt #:number',
        'courier_on_way' => 'Kurýer ýolda',
        'courier_eta' => '12-15 minutdan geler',
        'order_status' => 'Sargydyň ýagdaýy',
        'delivery' => 'Eltip bermek',
        'entrance' => 'podýezd :value',
        'floor' => 'gat :value',
        'apartment' => 'jaý :value',
        'open_in_map' => 'Kartada aç',
        'coordinates' => 'Koordinatalar',
        'order_items' => 'Sargydyň düzümi',
        'item_fallback' => 'Haryt',
        'quantity' => 'Sany: :count',
        'total' => 'Jemi',
        'contact_support' => 'Goldaw gullugyna ýaz',
        'status_changed' => 'Sargydyň ýagdaýy üýtgedi',
        'delivery_address' => 'Eltip bermek salgysy',
        'restaurant' => 'Restoran',
        'courier' => 'Kurýer',
        // Timeline step labels
        'steps' => [
            'pending' => 'Kabul edildi',
            'confirmed' => 'Tassyklandy',
            'preparing' => 'Taýýarlanýar',
            'ready' => 'Taýýar',
            'delivering' => 'Ýolda',
            'completed' => 'Eltildi',
        ],
        // Status badge texts
        'statuses' => [
            'pending' => 'Tassyklanmagyna garaşylýar',
            'confirmed' => 'Tassyklandy',
            'preparing' => 'Taýýarlanýar',
            'ready' => 'Almaga taýýar',
            'delivering' => 'Ýolda',
            'completed' => 'Eltildi',
            'delivered' => 'Eltildi',
            'cancelled' => 'Ýatyryldy',
        ],
    ],

    'success' => [
        'title' => 'Sargyt kabul edildi - RestoPWA',
        'heading' => 'Sargyt resmileşdirildi!',
        'subheading' => 'Sargydyňyz üstünlikli kabul edildi we ýakyn wagtda taýýarlanyp başlanar',
        'accepted' => 'Sargyt kabul edildi',
        'order_number' => 'Sargyt belgisi',
        'eta' => 'Garaşylýan wagt',
        'eta_value' => '30-45 min',
        'total_to_pay' => 'Tölenmeli jemi',
        'track' => 'Sargydy yzarla',
        'home' => 'Baş sahypa',
    ],

    'offline' => [
        'title' => 'Oflaýn - RestoPWA',
        'heading' => 'Internet ýok',
        'lost_connection' => 'Internet bilen baglanyşyk kesilen ýaly.',
        'not_cached' => 'Bu sahypa entek internetsiz işlemek üçin ýatda saklanmady.',
        'retry' => 'Gaýtadan synanyş',
        'home' => 'Baş sahypa',
        'badge' => 'Oflaýn tertip',
    ],

    'installer' => [
        'subtitle' => 'Öý ekranyna oturt',
        'install' => 'Oturt',
    ],
];
