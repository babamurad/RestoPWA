<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\Casts\MoneyCast;
use App\Domains\Order\Exceptions\InvalidStatusTransition;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Services\PaymentService;
use App\Domains\Promo\Services\PromoCodeService;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\OrderCancelReason;
use App\Enums\PaymentStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_PREPARING = 'preparing';

    // Same value as Order::STATUS_READY_FOR_PICKUP: couriers only see ready_for_pickup orders
    public const STATUS_READY = 'ready_for_pickup';

    public const STATUS_DELIVERING = 'delivering';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @param  array  $data  order as accepted by the API: `total`, `delivery_fee` and item prices
     *                       are whole minor units (tyiyn), see docs/00_Active/order_contract.md
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $restaurant = Restaurant::findOrFail($data['vendor_id']);

            $order = Order::create([
                'vendor_id' => $data['vendor_id'],
                'user_id' => $data['user_id'],
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'status' => self::STATUS_PENDING,
                'delivery_type' => $data['delivery_type'] ?? Order::DELIVERY_TYPE_DELIVERY,
                'payment_status' => PaymentStatus::UNPAID,
                'address' => $data['address'] ?? [],
                'items' => $data['items'],
                // $data comes from the order API, which counts money in minor units (tyiyn)
                'total' => MoneyCast::fromMinor($data['total']),
                'delivery_fee' => MoneyCast::fromMinor($data['delivery_fee'] ?? 0),
                'discount_amount' => MoneyCast::fromMinor($data['discount_amount'] ?? 0),
                'delivery_time' => $data['delivery_time'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'card',
                'comment' => $data['comment'] ?? null,
                'is_offline' => $data['is_offline'] ?? false,
                'metadata' => $data['metadata'] ?? null,
            ]);

            if (! empty($data['promo_code'])) {
                $itemsTotal = (int) collect($data['items'])->sum('total_price');
                $promo = app(PromoCodeService::class)->redeem($order, $data['promo_code'], $itemsTotal, (int) ($data['discount_amount'] ?? 0));
                $order->update(['promo_code_id' => $promo->id, 'promo_code' => $promo->code]);
            }

            app(PaymentService::class)->start($order);

            $order->statusHistory()->create([
                'from_status' => null,
                'to_status' => self::STATUS_PENDING,
                'metadata' => [
                    'created_via' => $data['created_via'] ?? 'web',
                    'is_offline' => $data['is_offline'] ?? false,
                ],
            ]);

            return $order;
        });
    }

    /**
     * Move an order to the next status. Only the steps of Order::getNextStatuses() are
     * allowed; the history record is written by the Order model itself.
     *
     * @throws InvalidStatusTransition
     */
    public function updateStatus(string $orderId, string $newStatus, array $metadata = []): Order
    {
        $order = Order::findOrFail($orderId);

        if (! $order->canTransitionTo($newStatus)) {
            throw new InvalidStatusTransition($order, $newStatus);
        }

        $order->statusChangeMeta = $metadata;
        $order->update(['status' => $newStatus]);

        return $order->fresh();
    }

    /**
     * Cancel an order: by the customer (only until the kitchen has started), by the
     * restaurant ("decline", with a reason) or by an admin. The observer then closes the
     * pending payment and the open courier offers, and tells the customer.
     *
     * @param  string  $by  one of Order::CANCELLED_BY_*
     *
     * @throws InvalidStatusTransition
     */
    public function cancel(Order $order, string $by, ?OrderCancelReason $reason = null, ?string $comment = null): Order
    {
        return DB::transaction(function () use ($order, $by, $reason, $comment) {
            // Lock: the restaurant may be moving the order on at the same moment
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $allowed = $order->canTransitionTo(Order::STATUS_CANCELLED)
                && ($by !== Order::CANCELLED_BY_CUSTOMER || $order->canBeCancelledByCustomer());

            if (! $allowed) {
                throw new InvalidStatusTransition($order, Order::STATUS_CANCELLED);
            }

            $order->statusChangeMeta = array_filter([
                'cancelled_by' => $by,
                'reason' => $reason?->value,
            ]);

            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'cancelled_by' => $by,
                'cancel_reason' => $reason,
                'cancel_comment' => $comment !== null && trim($comment) !== '' ? trim($comment) : null,
                'cancelled_at' => now(),
            ]);

            app(PromoCodeService::class)->release($order);

            return $order;
        });
    }

    public function getWorkingHours(Restaurant $restaurant): ?array
    {
        $settings = $restaurant->settings;

        if (! $settings || ! isset($settings['working_hours'])) {
            return null;
        }

        return $settings['working_hours'];
    }

    public function isWithinWorkingHours(Restaurant $restaurant, ?Carbon $time = null): bool
    {
        $workingHours = $this->getWorkingHours($restaurant);

        if (! $workingHours) {
            return true;
        }

        $time = $time ?? now();
        $dayOfWeek = strtolower($time->format('l'));

        if (! isset($workingHours[$dayOfWeek])) {
            return false;
        }

        $hours = $workingHours[$dayOfWeek];

        if (isset($hours['closed']) && $hours['closed']) {
            return false;
        }

        $openTime = Carbon::parse($hours['open'], $restaurant->settings['timezone'] ?? 'UTC');
        $closeTime = Carbon::parse($hours['close'], $restaurant->settings['timezone'] ?? 'UTC');

        return $time->between($openTime, $closeTime);
    }
}
