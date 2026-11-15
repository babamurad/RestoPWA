<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domains\Order\Models\Order;
use App\Events\OrderStatusUpdated;

class LogOrderStatusUpdate
{
    public function handle(OrderStatusUpdated $event): void
    {
        $order = Order::find($event->orderId);

        if (! $order) {
            return;
        }

        if ($order->status === $event->status) {
            return;
        }

        // The history record is written by the Order model on the status change
        $order->statusChangeMeta = array_merge($event->metadata, [
            'updated_via' => 'reverb_event',
            'timestamp' => $event->timestamp,
        ]);
        $order->update(['status' => $event->status]);
    }
}
