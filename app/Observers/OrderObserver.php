<?php

declare(strict_types=1);

namespace App\Observers;

use App\Domains\Order\Models\Order;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $newStatus = $order->status;

        if (in_array($newStatus, ['cooking', 'delivering', 'delivered'])) {
            $this->sendPushNotification($order, $newStatus);
        }

        if ($newStatus === 'delivered' && $order->courier_id) {
            $this->calculateCourierEarning($order);
        }
    }

    private function calculateCourierEarning(Order $order): void
    {
        $restaurant = $order->vendor;
        if (!$restaurant) return;
        
        $fixed = (float) $restaurant->courier_fixed_fee;
        $percent = (float) $restaurant->courier_percent_fee;
        
        $amount = $fixed;
        if ($percent > 0) {
            $amount += ($order->total * $percent) / 100;
        }

        if ($amount > 0) {
            \App\Domains\Logistics\Models\CourierEarning::firstOrCreate([
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
            $user = \App\Models\User::find($order->user_id);
            if ($user && $user->phone) {
                // Only send SMS for important updates like delivering or delivered to save money
                if (in_array($status, ['delivering', 'delivered'])) {
                    \App\Jobs\SendSmsJob::dispatch($user->phone, "RestoPWA: $body");
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Notification failed: '.$e->getMessage());
        }
    }
}
