<?php

declare(strict_types=1);

namespace App\Domains\Payment\Gateways;

use App\Domains\Order\Models\Order;
use App\Domains\Payment\Contracts\PaymentGateway;
use App\Domains\Payment\Models\PaymentTransaction;
use App\Enums\PaymentStatus;

/**
 * Cash (or card to the courier) on delivery: the transaction stays pending
 * until the order is delivered, the order stays unpaid until then.
 */
class CashGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'cash';
    }

    public function initiate(Order $order): PaymentTransaction
    {
        $order->updateQuietly(['payment_status' => PaymentStatus::UNPAID]);

        return PaymentTransaction::firstOrCreate(
            ['idempotency_key' => "order:{$order->id}:payment"],
            [
                'order_id' => $order->id,
                'provider' => $this->code(),
                'type' => PaymentTransaction::TYPE_PAYMENT,
                'status' => PaymentTransaction::STATUS_PENDING,
                'amount' => $order->getRawOriginal('total'),
                'currency' => 'TMT',
            ],
        );
    }
}
