<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Logistics\Models\CourierEarning;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Models\PaymentTransaction;
use App\Enums\PaymentStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off clean-up of orders created before the delivery fee fix: the server
 * used DELIVERY_FEE_DEFAULT=500 instead of the restaurant's tariff, so each
 * delivery order got 500 TMT of delivery.
 *
 * Orders where no money has moved yet (not finished, not paid) are corrected:
 * fee = restaurant's flat tariff the cart showed, total reduced accordingly.
 * Finished, paid, paid-out or courier-credited orders are only reported:
 * refunds are a business decision.
 */
class FixLegacyDeliveryFee extends Command
{
    protected $signature = 'orders:fix-legacy-delivery-fee
        {--amount=500 : Wrong delivery fee that was charged, TMT}
        {--before=2026-10-17 07:05:00 : Only orders created before this moment (UTC) — set to the deploy time of the fix}
        {--dry-run : Only show what would change}';

    protected $description = 'Correct the 500 TMT delivery fee of orders created before the delivery fee fix';

    public function handle(): int
    {
        $wrongFee = (float) $this->option('amount');
        $dryRun = (bool) $this->option('dry-run');

        $orders = Order::with('restaurant')
            ->where('delivery_fee', (int) round($wrongFee * 100)) // stored in minor units
            ->where('created_at', '<', $this->option('before'))
            ->orderBy('created_at')
            ->get();

        $fixed = [];
        $review = [];

        foreach ($orders as $order) {
            $correctFee = $order->isPickup() ? 0.0 : (float) ($order->restaurant?->delivery_fee ?? 0);
            if ($correctFee === $wrongFee) {
                continue; // the restaurant really charges this much
            }

            $newTotal = round((float) $order->total - $wrongFee + $correctFee, 2);
            $row = [
                '#'.$order->order_number,
                $order->created_at->format('d.m.Y H:i'),
                $order->status,
                $this->money($order->delivery_fee).' → '.$this->money($correctFee),
                $this->money($order->total).' → '.$this->money($newTotal),
            ];

            if ($reason = $this->blocker($order)) {
                $review[] = [...$row, $reason];

                continue;
            }

            if (! $dryRun) {
                DB::transaction(function () use ($order, $correctFee, $newTotal) {
                    $order->forceFill(['delivery_fee' => $correctFee, 'total' => $newTotal]);
                    // A data correction, not a change of the order: keep its dates and skip observers
                    Order::withoutTimestamps(fn () => $order->saveQuietly());

                    PaymentTransaction::where('order_id', $order->id)
                        ->where('status', PaymentTransaction::STATUS_PENDING)
                        ->update(['amount' => (int) round($newTotal * 100)]);
                });
            }

            $fixed[] = $row;
        }

        $headers = ['Заказ', 'Создан', 'Статус', 'Доставка', 'Итого'];

        $this->info(($dryRun ? 'Будут исправлены' : 'Исправлены').': '.count($fixed));
        if ($fixed) {
            $this->table($headers, $fixed);
        }

        $this->warn('Требуют решения вручную (деньги уже прошли): '.count($review));
        if ($review) {
            $this->table([...$headers, 'Почему не исправлен'], $review);
        }

        return self::SUCCESS;
    }

    /** Why the order must not be changed automatically, or null */
    private function blocker(Order $order): ?string
    {
        if ($order->vendor_settlement_id !== null) {
            return 'вошёл в выплату ресторану';
        }

        // Legacy orders finished before payment records existed may show "unpaid"
        // although the courier collected the cash: never rewrite them automatically
        if (in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], true)) {
            return 'заказ завершён — клиент, скорее всего, уже заплатил';
        }

        if ($order->payment_status === PaymentStatus::PAID
            || PaymentTransaction::where('order_id', $order->id)->where('status', PaymentTransaction::STATUS_SUCCEEDED)->exists()) {
            return 'оплачен клиентом';
        }

        if (CourierEarning::where('order_id', $order->id)->exists()) {
            return 'курьеру начислен заработок';
        }

        return null;
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, ',', ' ').' TMT';
    }
}
