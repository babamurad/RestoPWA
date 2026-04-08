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

        if (in_array($newStatus, ['cooking', 'delivering'])) {
            $this->sendPushNotification($order, $newStatus);
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
            ];

            $bodies = [
                'cooking' => 'Ресторан начал готовить ваш заказ',
                'delivering' => 'Курьер уже в пути к вам',
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
        } catch (\Throwable $e) {
            Log::warning('Push notification failed: '.$e->getMessage());
        }
    }
}
