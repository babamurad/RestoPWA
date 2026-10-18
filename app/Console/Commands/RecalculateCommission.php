<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Order\Models\Order;
use App\Domains\Payment\Services\CommissionCalculator;
use Illuminate\Console\Command;

/**
 * One-off after the commission rule change (food total only, pickups included):
 * recalculates finished orders that are not in a payout yet.
 */
class RecalculateCommission extends Command
{
    protected $signature = 'orders:recalculate-commission {--dry-run : Only show what would change}';

    protected $description = 'Recalculate platform commission of finished orders not yet included in a payout';

    public function handle(CommissionCalculator $calculator): int
    {
        $changed = 0;
        $before = 0.0;
        $after = 0.0;

        Order::with('restaurant')
            ->whereIn('status', Order::FINISHED_STATUSES)
            ->whereNull('vendor_settlement_id')
            ->chunkById(200, function ($orders) use ($calculator, &$changed, &$before, &$after) {
                foreach ($orders as $order) {
                    $old = (float) $order->commission_amount;
                    $new = $calculator->amount($order);

                    if ($old === $new) {
                        continue;
                    }

                    $changed++;
                    $before += $old;
                    $after += $new;

                    if (! $this->option('dry-run')) {
                        $calculator->apply($order);
                    }
                }
            });

        $this->info(sprintf(
            '%s %d orders: commission %.2f → %.2f TMT',
            $this->option('dry-run') ? 'Would update' : 'Updated',
            $changed,
            $before,
            $after,
        ));

        return self::SUCCESS;
    }
}
