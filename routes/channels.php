<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('courier.{courierId}', function ($user, $courierId) {
    return $user->isCourier() && $user->courierProfile?->id === $courierId;
});

Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    $order = \App\Domains\Order\Models\Order::find($orderId);
    return $order && ((string) $user->id === (string) $order->user_id || $user->isAdmin() || $user->isRestaurateur());
});
