<?php

namespace App\Events;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Order\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourierLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Courier $courier)
    {
    }

    public function broadcastOn(): array
    {
        $channels = [];
        $activeOrders = Order::where('courier_id', $this->courier->id)
            ->whereIn('status', [Order::STATUS_READY_FOR_PICKUP, Order::STATUS_DELIVERING])
            ->get();

        foreach ($activeOrders as $order) {
            $channels[] = new PrivateChannel('order.' . $order->id);
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'courier_id' => $this->courier->id,
            'lat' => $this->courier->current_lat,
            'lon' => $this->courier->current_lon,
        ];
    }
}
