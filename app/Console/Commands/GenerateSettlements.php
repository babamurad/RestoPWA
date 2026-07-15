<?php

namespace App\Console\Commands;

use App\Domains\Order\Models\Order;
use App\Models\VendorSettlement;
use App\Domains\Vendor\Models\Restaurant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateSettlements extends Command
{
    protected $signature = 'settlements:generate {--vendor=} {--from=} {--to=}';
    protected $description = 'Generate vendor settlements for delivered orders';

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
                // Get all delivered orders for this vendor in the date range without a settlement
                $orders = Order::where('vendor_id', $restaurant->id)
                    ->where('status', 'delivered')
                    ->whereNull('vendor_settlement_id')
                    ->whereBetween('updated_at', [$from, $to])
                    ->get();

                if ($orders->isEmpty()) {
                    return;
                }

                $gross = $orders->sum('total');
                $commission = $orders->sum('commission_amount');
                $net = $gross - $commission;

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
