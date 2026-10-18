<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\Order\Models\Order;

/**
 * Platform commission: the restaurant's percent of the food total.
 * The delivery fee is excluded (it goes to the courier); pickup orders pay it too.
 */
class CommissionCalculator
{
    public function amount(Order $order): float
    {
        $percent = (float) $order->restaurant?->commission_percent;

        if ($percent <= 0) {
            return 0.0;
        }

        return round($order->foodTotal() * $percent / 100, 2);
    }

    /**
     * Store the commission on a finished order. Orders already included in a
     * payout are never touched: the payout was calculated from them.
     */
    public function apply(Order $order): void
    {
        if ($order->vendor_settlement_id !== null || ! $order->isFinished()) {
            return;
        }

        $amount = $this->amount($order);

        if ((float) $order->commission_amount !== $amount) {
            $order->commission_amount = $amount;
            // Keep updated_at: payouts pick orders by it, a recalculated old
            // order must not move into the current payout period
            Order::withoutTimestamps(fn () => $order->saveQuietly());
        }
    }
}
