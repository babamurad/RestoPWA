<?php

declare(strict_types=1);

namespace App\Observers;

use App\Domains\Logistics\Models\CourierEarning;
use App\Domains\Logistics\Services\CourierDispatcher;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Services\CommissionCalculator;
use App\Domains\Payment\Services\PaymentService;
use App\Events\OrderStatusUpdated;
use App\Jobs\SendSmsJob;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function created(Order $order): void
    {
        $this->broadcastStatus($order);
    }

    /**
     * The DB cascade on reviews.order_id would drop the review silently and
     * leave the restaurant rating stale; delete it through the model instead
     * so ReviewObserver recalculates.
     */
    public function deleting(Order $order): void
    {
        $order->review?->delete();
    }

    public function updated(Order $order): void
    {
        $dispatcher = app(CourierDispatcher::class);

        // Order got a courier (accepted offer, list, manual assignment): close other offers
        if ($order->wasChanged('courier_id') && $order->courier_id !== null) {
            $dispatcher->cancelPendingOffers($order);
        }

        if (! $order->wasChanged('status')) {
            return;
        }

        // Ready for pickup with no courier yet: offer it to couriers automatically
        if ($order->status === Order::STATUS_READY_FOR_PICKUP && $order->courier_id === null) {
            DB::afterCommit(fn () => $dispatcher->dispatch($order));
        }

        $this->broadcastStatus($order);

        $newStatus = $order->status;

        if (in_array($newStatus, ['cooking', 'delivering', 'delivered'])) {
            $this->sendPushNotification($order, $newStatus);
        }

        if ($newStatus === 'delivered' && $order->courier_id) {
            $this->calculateCourierEarning($order);
        }

        // Courier delivery or pickup handed over: the platform earns its commission
        if ($order->isFinished()) {
            app(CommissionCalculator::class)->apply($order);
        }

        // Customer got the order (courier delivery or pickup closed on the kanban)
        if (in_array($newStatus, ['delivered', 'completed'], true)) {
            app(PaymentService::class)->captureCashOnCompletion($order);
        }

        if ($newStatus === Order::STATUS_CANCELLED) {
            app(PaymentService::class)->cancelPending($order);
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
        $event = new OrderStatusUpdated($order->id, $order->status, [], $order->vendor_id);

        // Orders are created/accepted inside DB transactions: send only after
        // commit, otherwise listeners (kanban) re-read the DB before the change
        // is visible. Runs immediately when no transaction is open; dropped on rollback.
        DB::afterCommit(function () use ($event) {
            try {
                Broadcast::queue($event);
            } catch (\Throwable $e) {
                Log::warning('[OrderObserver] Status broadcast failed', [
                    'order_id' => $event->orderId,
                    'status' => $event->status,
                    'exception' => $e->getMessage(),
                ]);
            }
        });
    }

    private function calculateCourierEarning(Order $order): void
    {
        // The courier gets the whole delivery fee the customer paid
        // (the district's price set by the restaurant).
        $amount = (float) $order->delivery_fee;

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

            // Push and SMS go out in the customer's language
            $user = User::find($order->user_id);
            $locale = $user?->locale ?? config('app.locale');
            $key = in_array($status, ['cooking', 'delivering', 'delivered'], true) ? $status : 'default';

            $title = __("notifications.order.{$key}.title", [], $locale);
            $body = __("notifications.order.{$key}.body", [], $locale);

            $pushService->sendToUser(
                $order->user_id,
                $title,
                $body,
                [
                    'order_id' => $order->id,
                    'status' => $status,
                ]
            );

            // Only send SMS for important updates like delivering or delivered to save money
            if ($user && $user->phone && in_array($status, ['delivering', 'delivered'])) {
                SendSmsJob::dispatch($user->phone, 'RestoPWA: '.$body);
            }
        } catch (\Throwable $e) {
            Log::warning('Notification failed: '.$e->getMessage());
        }
    }
}
