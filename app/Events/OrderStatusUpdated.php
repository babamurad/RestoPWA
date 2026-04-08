<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public string $orderId;

    public string $status;

    public string $timestamp;

    public array $metadata;

    public ?string $vendorId;

    public function __construct(string $orderId, string $status, array $metadata = [], ?string $vendorId = null)
    {
        $this->orderId = $orderId;
        $this->status = $status;
        $this->timestamp = now()->toIso8601String();
        $this->metadata = $metadata;
        $this->vendorId = $vendorId;
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('orders.'.$this->orderId),
        ];

        if ($this->vendorId) {
            $channels[] = new Channel('restaurant.'.$this->vendorId);
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'status' => $this->status,
            'timestamp' => $this->timestamp,
            'metadata' => $this->metadata,
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.status.updated';
    }
}
