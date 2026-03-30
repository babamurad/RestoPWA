<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Domains\Order\Models\OrderStatusHistory;

class LogOrderStatusUpdate
{
    public function handle(OrderStatusUpdated $event): void
    {
        $order = \App\Domains\Order\Models\Order::find($event->orderId);

        if (!$order) {
            return;
        }

        $fromStatus = $order->status;

        $order->update(['status' => $event->status]);

        OrderStatusHistory::create([
            'order_id' => $event->orderId,
            'from_status' => $fromStatus,
            'to_status' => $event->status,
            'metadata' => array_merge($event->metadata, [
                'updated_via' => 'reverb_event',
                'timestamp' => $event->timestamp,
            ]),
        ]);
    }
}
