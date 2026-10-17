<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\Order\Models\Order;
use App\Domains\Payment\Contracts\PaymentGateway;
use App\Domains\Payment\Gateways\CashGateway;
use App\Domains\Payment\Models\PaymentTransaction;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Ties payments to the order lifecycle: open the payment when the order is
 * created, collect cash on delivery, close the open payment on cancellation.
 */
class PaymentService
{
    /** @var array<string, class-string<PaymentGateway>> payment_method => gateway */
    private const GATEWAYS = [
        'cash' => CashGateway::class,
    ];

    public function gatewayFor(string $paymentMethod): PaymentGateway
    {
        $class = self::GATEWAYS[$paymentMethod]
            ?? throw new InvalidArgumentException("No payment gateway for method [{$paymentMethod}]");

        return app($class);
    }

    /**
     * Open the payment for a new order. A method without a gateway (e.g. a
     * legacy "card" default) must not break order creation: the order simply
     * stays unpaid without a transaction.
     */
    public function start(Order $order): ?PaymentTransaction
    {
        $method = $order->payment_method ?? 'cash';
        if (! isset(self::GATEWAYS[$method])) {
            Log::warning('[Payment] No gateway for payment method, order left unpaid', [
                'order_id' => $order->id,
                'payment_method' => $method,
            ]);

            return null;
        }

        return $this->gatewayFor($method)->initiate($order);
    }

    /**
     * Order handed to the customer: the courier (or the restaurant, for pickup)
     * has collected the cash. Online payments are confirmed by their provider
     * instead, so only cash is captured here.
     */
    public function captureCashOnCompletion(Order $order): void
    {
        // Orders placed before payment_transactions existed have no payment yet:
        // open it now so they don't stay "unpaid" forever after delivery.
        if (! PaymentTransaction::where('order_id', $order->id)->exists()) {
            $this->start($order);
        }

        $transaction = $this->pendingPayment($order);
        if (! $transaction || $transaction->provider !== 'cash') {
            return;
        }

        $transaction->update(['status' => PaymentTransaction::STATUS_SUCCEEDED]);
        $order->updateQuietly(['payment_status' => PaymentStatus::PAID]);
    }

    /** Order cancelled before payment: close the open transaction. */
    public function cancelPending(Order $order): void
    {
        $this->pendingPayment($order)?->update(['status' => PaymentTransaction::STATUS_CANCELLED]);
    }

    private function pendingPayment(Order $order): ?PaymentTransaction
    {
        return PaymentTransaction::where('order_id', $order->id)
            ->where('type', PaymentTransaction::TYPE_PAYMENT)
            ->where('status', PaymentTransaction::STATUS_PENDING)
            ->latest()
            ->first();
    }
}
