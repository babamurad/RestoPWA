<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourierLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public string $orderId;
    public float $lat;
    public float $lon;
    public ?float $heading;
    public string $timestamp;

    public function __construct(string $orderId, float $lat, float $lon, ?float $heading = null)
    {
        $this->orderId = $orderId;
        $this->lat = $lat;
        $this->lon = $lon;
        $this->heading = $heading;
        $this->timestamp = now()->toIso8601String();
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('tracking.' . $this->orderId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'lat' => $this->lat,
            'lon' => $this->lon,
            'heading' => $this->heading,
            'timestamp' => $this->timestamp,
        ];
    }

    public function broadcastAs(): string
    {
        return 'courier.location.updated';
    }
}
