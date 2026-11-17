<?php

/**
 * How the platform earns from restaurants.
 *
 *  - subscription: restaurants pay a monthly fee (one of the plans below); no commission
 *    is taken from orders and payouts (settlements:generate) are not made.
 *  - commission:   the platform takes restaurants.commission_percent from each order and
 *    pays the rest out weekly (settlements:generate).
 *
 * The commission/payout code stays in place: switch BILLING_MODE=commission to bring it back.
 */
return [

    'mode' => env('BILLING_MODE', 'subscription'),

    // Monthly price per plan, TMT. The admin picks a plan for each restaurant
    // by its order volume (restaurants.subscription_plan).
    'plans' => [
        'small' => [
            'label' => 'Малый',
            'orders' => 'до 300 заказов в месяц',
            'price' => (float) env('BILLING_PLAN_SMALL_PRICE', 300),
        ],
        'medium' => [
            'label' => 'Средний',
            'orders' => '300–1000 заказов в месяц',
            'price' => (float) env('BILLING_PLAN_MEDIUM_PRICE', 600),
        ],
        'large' => [
            'label' => 'Крупный',
            'orders' => 'более 1000 заказов в месяц',
            'price' => (float) env('BILLING_PLAN_LARGE_PRICE', 1000),
        ],
    ],

];
