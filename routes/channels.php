<?php

use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('courier.{courierId}', function ($user, $courierId) {
    return $user->isCourier() && $user->courierProfile?->id === $courierId;
});

// Order status and courier location for one order: its customer, an admin,
// or the restaurateur who owns the order's restaurant.
Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    $order = Order::with('restaurant')->find($orderId);
    if (! $order) {
        return false;
    }

    if ((string) $user->id === (string) $order->user_id || $user->isAdmin()) {
        return true;
    }

    return $user->isRestaurateur() && $order->restaurant && $user->canAccessTenant($order->restaurant);
});

// All order updates of one restaurant (vendor kanban board).
Broadcast::channel('restaurant.{vendorId}', function ($user, $vendorId) {
    if ($user->isAdmin()) {
        return true;
    }

    $restaurant = Restaurant::find($vendorId);

    return $user->isRestaurateur() && $restaurant && $user->canAccessTenant($restaurant);
});
