<?php

namespace App\Console\Commands;

use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\VendorSettlement;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateSettlements extends Command
{
    protected $signature = 'settlements:generate {--vendor=} {--from=} {--to=}';

    protected $description = 'Generate vendor settlements for finished orders (delivered or picked up)';

    public function handle()
    {
        $vendorId = $this->option('vendor');

        // By default generate for last week
        $from = $this->option('from') ? Carbon::parse($this->option('from'))->startOfDay() : Carbon::now()->subWeek()->startOfDay();
        $to = $this->option('to') ? Carbon::parse($this->option('to'))->endOfDay() : Carbon::now()->endOfDay();

        $query = Restaurant::query();
        if ($vendorId) {
            $query->where('id', $vendorId);
        }

        $restaurants = $query->get();

        foreach ($restaurants as $restaurant) {
            DB::transaction(function () use ($restaurant, $from, $to) {
                // Finished orders (delivered or picked up) of this vendor in the range without a settlement
                $orders = Order::where('vendor_id', $restaurant->id)
                    ->finished()
                    ->whereNull('vendor_settlement_id')
                    ->whereBetween('updated_at', [$from, $to])
                    ->get();

                if ($orders->isEmpty()) {
                    return;
                }

                // The restaurant is paid for the food; delivery fees belong to couriers
                $gross = round($orders->sum(fn (Order $o) => $o->foodTotal()), 2);
                $commission = round($orders->sum('commission_amount'), 2);
                $net = round($gross - $commission, 2);

                $settlement = VendorSettlement::create([
                    'restaurant_id' => $restaurant->id,
                    'period_from' => $from,
                    'period_to' => $to,
                    'gross_amount' => $gross,
                    'commission_amount' => $commission,
                    'net_payable' => $net,
                    'status' => 'draft',
                ]);

                // Order is using saving without firing events, to not trigger loops.
                // However, bulk update is faster.
                Order::whereIn('id', $orders->pluck('id'))->update(['vendor_settlement_id' => $settlement->id]);

                $this->info("Generated settlement for vendor {$restaurant->name}: Net {$net}");
            });
        }

        $this->info('Settlements generation completed.');
    }
}
