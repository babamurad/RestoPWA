<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use App\Domains\Logistics\Jobs\ExpireCourierOffer;
use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\CourierOffer;
use App\Domains\Order\Models\Order;
use App\Events\CourierOfferCreated;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Automatic courier dispatch. When a delivery order is ready for pickup it is
 * offered to one courier at a time:
 *  1. couriers bound to the order's restaurant, then the general pool;
 *  2. within a group, the one who waited longest since the last assignment;
 *  3. only couriers on shift (available) without an active delivery, and never
 *     the same courier twice for one order.
 * A courier who declines or doesn't answer in time passes it to the next one.
 * When nobody is left the order stays for manual assignment by the restaurant.
 */
class CourierDispatcher
{
    public function dispatch(Order $order): ?CourierOffer
    {
        return DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $order
                || $order->isPickup()
                || $order->courier_id !== null
                || $order->status !== Order::STATUS_READY_FOR_PICKUP) {
                return null;
            }

            $hasPendingOffer = CourierOffer::where('order_id', $order->id)
                ->where('status', CourierOffer::STATUS_PENDING)
                ->exists();
            if ($hasPendingOffer) {
                return null;
            }

            $courier = $this->nextCandidate($order);
            if (! $courier) {
                Log::info('[Dispatch] No free courier, order left for manual assignment', ['order_id' => $order->id]);

                return null;
            }

            $offer = CourierOffer::create([
                'order_id' => $order->id,
                'courier_id' => $courier->id,
                'status' => CourierOffer::STATUS_PENDING,
                'expires_at' => now()->addSeconds(config('logistics.offer_timeout_seconds')),
            ]);

            DB::afterCommit(function () use ($offer) {
                $this->notify($offer);
                ExpireCourierOffer::dispatch($offer->id)->delay($offer->expires_at);
            });

            return $offer;
        });
    }

    public function nextCandidate(Order $order): ?Courier
    {
        $alreadyOffered = CourierOffer::where('order_id', $order->id)->pluck('courier_id');

        return Courier::query()
            ->where('status', Courier::STATUS_AVAILABLE)
            ->serving($order->vendor_id)
            ->whereNotIn('id', $alreadyOffered)
            ->whereDoesntHave('activeOrders')
            ->with('restaurants:id')
            ->get()
            ->sortBy([
                // Bound to this restaurant first, then the general pool
                fn (Courier $a, Courier $b) => $b->restaurants->contains('id', $order->vendor_id)
                    <=> $a->restaurants->contains('id', $order->vendor_id),
                // Waited longest (never assigned = waited the longest)
                fn (Courier $a, Courier $b) => ($a->last_assigned_at?->getTimestamp() ?? 0)
                    <=> ($b->last_assigned_at?->getTimestamp() ?? 0),
            ])
            ->first();
    }

    /**
     * Courier accepts their offer: the order becomes theirs (same result as
     * taking it from the list of available orders).
     *
     * @throws RuntimeException with a user-facing reason when it can't be accepted
     */
    public function accept(CourierOffer $offer, Courier $courier): Order
    {
        return DB::transaction(function () use ($offer, $courier) {
            $offer = CourierOffer::whereKey($offer->id)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($offer->order_id)->lockForUpdate()->firstOrFail();

            if ($offer->courier_id !== $courier->id) {
                throw new RuntimeException('Offer belongs to another courier', 403);
            }
            if ($offer->status !== CourierOffer::STATUS_PENDING || $offer->expires_at->isPast()) {
                throw new RuntimeException('Offer is no longer valid', 409);
            }
            if ($order->courier_id !== null || $order->status !== Order::STATUS_READY_FOR_PICKUP) {
                throw new RuntimeException('Order already taken', 409);
            }

            $offer->update(['status' => CourierOffer::STATUS_ACCEPTED, 'responded_at' => now()]);
            $order->update(['courier_id' => $courier->id, 'status' => Order::STATUS_DELIVERING]);
            $courier->update(['last_assigned_at' => now()]);

            return $order;
        });
    }

    public function decline(CourierOffer $offer): void
    {
        $updated = CourierOffer::whereKey($offer->id)
            ->where('status', CourierOffer::STATUS_PENDING)
            ->update(['status' => CourierOffer::STATUS_DECLINED, 'responded_at' => now()]);

        if ($updated) {
            $this->dispatch($offer->order);
        }
    }

    /** Timeout: the offer passes to the next courier. */
    public function expire(CourierOffer $offer): void
    {
        $updated = CourierOffer::whereKey($offer->id)
            ->where('status', CourierOffer::STATUS_PENDING)
            ->where('expires_at', '<=', now())
            ->update(['status' => CourierOffer::STATUS_EXPIRED]);

        if ($updated) {
            $this->dispatch($offer->order);
        }
    }

    /** Backup for a lost/late queue job: expire every overdue offer. */
    public function expireOverdue(): int
    {
        $overdue = CourierOffer::where('status', CourierOffer::STATUS_PENDING)
            ->where('expires_at', '<=', now())
            ->get();

        $overdue->each(fn (CourierOffer $offer) => $this->expire($offer));

        return $overdue->count();
    }

    /** The order got a courier some other way: close its open offers. */
    public function cancelPendingOffers(Order $order): void
    {
        CourierOffer::where('order_id', $order->id)
            ->where('status', CourierOffer::STATUS_PENDING)
            ->update(['status' => CourierOffer::STATUS_CANCELLED, 'responded_at' => now()]);
    }

    private function notify(CourierOffer $offer): void
    {
        try {
            Broadcast::queue(new CourierOfferCreated($offer));
        } catch (\Throwable $e) {
            Log::warning('[Dispatch] Offer broadcast failed', ['offer_id' => $offer->id, 'exception' => $e->getMessage()]);
        }
    }
}
