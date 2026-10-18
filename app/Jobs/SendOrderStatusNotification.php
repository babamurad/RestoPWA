<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domains\Order\Models\Order;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Tells the customer their order moved on: web push + SMS for the key steps,
 * in the customer's language. Runs on the queue so a slow push service never
 * holds up the restaurant's or the courier's request.
 */
class SendOrderStatusNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** Statuses the customer hears about */
    public const NOTIFIED_STATUSES = ['cooking', Order::STATUS_DELIVERING, Order::STATUS_DELIVERED];

    /** SMS costs money: only when the food is on its way and when it arrived */
    public const SMS_STATUSES = [Order::STATUS_DELIVERING, Order::STATUS_DELIVERED];

    /** A repeated push is worse than a lost one: no retries (SMS retries in its own job) */
    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(
        public string $orderId,
        public string $status,
    ) {}

    public function handle(): void
    {
        $order = Order::find($this->orderId);
        $user = $order?->user_id ? User::find($order->user_id) : null;
        if (! $user) {
            return;
        }

        $locale = $user->locale ?? config('app.locale');
        $key = in_array($this->status, self::NOTIFIED_STATUSES, true) ? $this->status : 'default';
        $title = __("notifications.order.{$key}.title", [], $locale);
        $body = __("notifications.order.{$key}.body", [], $locale);

        // Independent channels: a push failure (e.g. VAPID keys not configured) must not cost the SMS
        try {
            app(PushNotificationService::class)->sendToUser($user->id, $title, $body, [
                'order_id' => $order->id,
                'status' => $this->status,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[Notifications] Push failed: '.$e->getMessage(), ['order_id' => $order->id]);
        }

        if ($user->phone && in_array($this->status, self::SMS_STATUSES, true)) {
            SendSmsJob::dispatch($user->phone, 'RestoPWA: '.$body);
        }
    }
}
