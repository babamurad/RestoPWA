<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderStatusHistory;
use App\Domains\Vendor\Models\Restaurant;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY = 'ready';
    public const STATUS_DELIVERING = 'delivering';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $restaurant = Restaurant::findOrFail($data['vendor_id']);

            $order = Order::create([
                'vendor_id' => $data['vendor_id'],
                'user_id' => $data['user_id'],
                'status' => self::STATUS_PENDING,
                'payment_status' => 'pending',
                'address' => $data['address'] ?? [],
                'items' => $data['items'],
                'total' => $data['total'],
                'delivery_fee' => $data['delivery_fee'] ?? 0,
                'delivery_time' => $data['delivery_time'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'card',
                'comment' => $data['comment'] ?? null,
                'is_offline' => $data['is_offline'] ?? false,
            ]);

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

    public function updateStatus(string $orderId, string $newStatus, array $metadata = []): Order
    {
        $order = Order::findOrFail($orderId);
        $oldStatus = $order->status;

        $order->update(['status' => $newStatus]);

        $order->statusHistory()->create([
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'metadata' => $metadata,
        ]);

        return $order->fresh();
    }

    public function getWorkingHours(Restaurant $restaurant): ?array
    {
        $settings = $restaurant->settings;
        
        if (!$settings || !isset($settings['working_hours'])) {
            return null;
        }

        return $settings['working_hours'];
    }

    public function isWithinWorkingHours(Restaurant $restaurant, ?\Carbon\Carbon $time = null): bool
    {
        $workingHours = $this->getWorkingHours($restaurant);
        
        if (!$workingHours) {
            return true;
        }

        $time = $time ?? now();
        $dayOfWeek = strtolower($time->format('l'));
        
        if (!isset($workingHours[$dayOfWeek])) {
            return false;
        }

        $hours = $workingHours[$dayOfWeek];
        
        if (isset($hours['closed']) && $hours['closed']) {
            return false;
        }

        $openTime = \Carbon\Carbon::parse($hours['open'], $restaurant->settings['timezone'] ?? 'UTC');
        $closeTime = \Carbon\Carbon::parse($hours['close'], $restaurant->settings['timezone'] ?? 'UTC');

        return $time->between($openTime, $closeTime);
    }
}
