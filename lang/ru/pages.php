<?php

// Customer-facing server-rendered pages (order tracking, success, offline, PWA installer)
return [
    'tracking' => [
        'title' => 'Отслеживание заказа',
        'order_number' => 'Заказ #:number',
        'courier_on_way' => 'Курьер в пути',
        'courier_eta' => 'Будет через 12-15 мин',
        'order_status' => 'Статус заказа',
        'delivery' => 'Доставка',
        'entrance' => 'п. :value',
        'floor' => 'эт. :value',
        'apartment' => 'кв. :value',
        'open_in_map' => 'Открыть в карте',
        'coordinates' => 'Координаты',
        'order_items' => 'Состав заказа',
        'item_fallback' => 'Товар',
        'quantity' => 'Кол-во: :count',
        'total' => 'Итого',
        'contact_support' => 'Написать в поддержку',
        'status_changed' => 'Статус заказа изменен',
        'delivery_address' => 'Адрес доставки',
        'restaurant' => 'Ресторан',
        'courier' => 'Курьер',
        // Timeline step labels
        'steps' => [
            'pending' => 'Принят',
            'confirmed' => 'Подтвержден',
            'preparing' => 'Готовится',
            'ready' => 'Готов',
            'delivering' => 'В пути',
            'completed' => 'Доставлен',
        ],
        // Status badge texts
        'statuses' => [
            'pending' => 'Ожидает подтверждения',
            'confirmed' => 'Подтвержден',
            'preparing' => 'Готовится',
            'ready' => 'Готов к выдаче',
            'delivering' => 'В пути',
            'completed' => 'Доставлен',
            'delivered' => 'Доставлен',
            'cancelled' => 'Отменен',
        ],
    ],

    'success' => [
        'title' => 'Заказ оформлен - RestoPWA',
        'heading' => 'Заказ оформлен!',
        'subheading' => 'Ваш заказ успешно принят и скоро будет передан в работу',
        'accepted' => 'Заказ принят',
        'order_number' => 'Номер заказа',
        'eta' => 'Ожидаемое время',
        'eta_value' => '30-45 мин',
        'total_to_pay' => 'Итого к оплате',
        'track' => 'Отследить заказ',
        'home' => 'На главную',
    ],

    'offline' => [
        'title' => 'Офлайн - RestoPWA',
        'heading' => 'Нет сети',
        'lost_connection' => 'Похоже, соединение с интернетом потеряно.',
        'not_cached' => 'Эта страница еще не была сохранена для работы без сети.',
        'retry' => 'Попробовать снова',
        'home' => 'На главную',
        'badge' => 'Офлайн режим',
    ],

    'installer' => [
        'subtitle' => 'Установить на домашний экран',
        'install' => 'Установить',
    ],
];
