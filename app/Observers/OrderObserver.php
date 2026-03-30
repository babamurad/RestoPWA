<?php

declare(strict_types=1);

namespace App\Observers;

use App\Domains\Order\Models\Order;
use App\Services\PushNotificationService;

class OrderObserver
{
    public function __construct(
        private PushNotificationService $pushService
    ) {}

    public function updated(Order $order): void
    {
        if (!$order->wasChanged('status')) {
            return;
        }

        $oldStatus = $order->getOriginal('status');
        $newStatus = $order->status;

        if (in_array($newStatus, ['cooking', 'delivering'])) {
            $this->sendPushNotification($order, $newStatus);
        }
    }

    private function sendPushNotification(Order $order, string $status): void
    {
        if (!$order->user_id) {
            return;
        }

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

        $this->pushService->sendToUser(
            $order->user_id,
            $title,
            $body,
            [
                'order_id' => $order->id,
                'status' => $status,
            ]
        );
    }
}
