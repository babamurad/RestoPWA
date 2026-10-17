<?php

declare(strict_types=1);

namespace App\Events;

use App\Domains\Logistics\Models\CourierOffer;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/** A new order offered to the courier (private courier.{id} channel). */
class CourierOfferCreated implements ShouldBroadcastNow
{
    public function __construct(public CourierOffer $offer) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('courier.'.$this->offer->courier_id)];
    }

    public function broadcastAs(): string
    {
        return 'courier.offer.created';
    }

    public function broadcastWith(): array
    {
        return [
            'offer_id' => $this->offer->id,
            'order_id' => $this->offer->order_id,
            'expires_at' => $this->offer->expires_at->toIso8601String(),
        ];
    }
}
