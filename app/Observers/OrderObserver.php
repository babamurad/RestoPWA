<?php

declare(strict_types=1);

namespace App\Observers;

use App\Domains\Logistics\Models\CourierEarning;
use App\Domains\Order\Models\Order;
use App\Events\OrderStatusUpdated;
use App\Jobs\SendSmsJob;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function created(Order $order): void
    {
        $this->broadcastStatus($order);
    }

    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $this->broadcastStatus($order);

        $newStatus = $order->status;

        if (in_array($newStatus, ['cooking', 'delivering', 'delivered'])) {
            $this->sendPushNotification($order, $newStatus);
        }

        if ($newStatus === 'delivered') {
            if ($order->courier_id) {
                $this->calculateCourierEarning($order);
            }
            $this->calculateMarketplaceCommission($order);
        }
    }

    /**
     * Push the status to the order's and the restaurant's private channels.
     *
     * Broadcast::queue() sends the event to the websocket server without
     * dispatching it to listeners: LogOrderStatusUpdate treats OrderStatusUpdated
     * as a "change status" command and would update the order again.
     * A websocket outage must never block an order update, so failures are only logged.
     */
    private function broadcastStatus(Order $order): void
    {
        try {
            Broadcast::queue(new OrderStatusUpdated($order->id, $order->status, [], $order->vendor_id));
        } catch (\Throwable $e) {
            Log::warning('[OrderObserver] Status broadcast failed', [
                'order_id' => $order->id,
                'status' => $order->status,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function calculateMarketplaceCommission(Order $order): void
    {
        $restaurant = $order->restaurant;
        if (! $restaurant) {
            return;
        }

        $percent = (float) $restaurant->commission_percent;
        if ($percent > 0 && $order->total > 0) {
            $commission = round(($order->total * $percent) / 100);
            $order->commission_amount = $commission;
            // Need to save without triggering events to prevent infinite loops,
            // but since we're in observer and just updating a specific field, we can use saveQuietly
            $order->saveQuietly();
        }
    }

    private function calculateCourierEarning(Order $order): void
    {
        $restaurant = $order->restaurant;
        if (! $restaurant) {
            return;
        }

        $fixed = (float) $restaurant->courier_fixed_fee;
        $percent = (float) $restaurant->courier_percent_fee;

        $amount = $fixed;
        if ($percent > 0) {
            $amount += ($order->total * $percent) / 100;
        }

        if ($amount > 0) {
            CourierEarning::firstOrCreate([
                'order_id' => $order->id,
                'courier_id' => $order->courier_id,
            ], [
                'amount' => $amount,
                'status' => 'pending',
            ]);
        }
    }

    private function sendPushNotification(Order $order, string $status): void
    {
        if (! $order->user_id) {
            return;
        }

        try {
            $pushService = app(PushNotificationService::class);

            $titles = [
                'cooking' => 'Заказ начали готовить',
                'delivering' => 'Заказ в пути',
                'delivered' => 'Заказ доставлен',
            ];

            $bodies = [
                'cooking' => 'Ресторан начал готовить ваш заказ',
                'delivering' => 'Курьер уже в пути к вам',
                'delivered' => 'Ваш заказ доставлен',
            ];

            $title = $titles[$status] ?? 'Статус заказа изменён';
            $body = $bodies[$status] ?? '';

            $pushService->sendToUser(
                $order->user_id,
                $title,
                $body,
                [
                    'order_id' => $order->id,
                    'status' => $status,
                ]
            );

            // Send SMS
            $user = User::find($order->user_id);
            if ($user && $user->phone) {
                // Only send SMS for important updates like delivering or delivered to save money
                if (in_array($status, ['delivering', 'delivered'])) {
                    SendSmsJob::dispatch($user->phone, "RestoPWA: $body");
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Notification failed: '.$e->getMessage());
        }
    }
}
