<?php

namespace Tests\Feature;

use App\Console\Commands\GenerateSettlements;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MarketplaceCommissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_commission_calculated_on_delivery(): void
    {
        $restaurant = Restaurant::factory()->create([
            'commission_percent' => 10.00,
        ]);

        $order = Order::factory()->create([
            'vendor_id' => $restaurant->id,
            'status' => Order::STATUS_DELIVERING,
            'total' => 100000,
        ]);

        $order->update(['status' => Order::STATUS_DELIVERED]);

        $this->assertEquals(100, $order->fresh()->commission_amount);
    }

    public function test_settlements_generation_command(): void
    {
        $restaurant = Restaurant::factory()->create([
            'commission_percent' => 10.00,
        ]);

        $order1 = Order::factory()->create([
            'vendor_id' => $restaurant->id,
            'status' => Order::STATUS_DELIVERED,
            'total' => 1000,
            'commission_amount' => 100,
            'updated_at' => Carbon::now()->subDays(2),
        ]);

        $order2 = Order::factory()->create([
            'vendor_id' => $restaurant->id,
            'status' => Order::STATUS_DELIVERED,
            'total' => 2000,
            'commission_amount' => 200,
            'updated_at' => Carbon::now()->subDays(2),
        ]);

        $this->artisan('settlements:generate')->assertSuccessful();

        $this->assertDatabaseHas('vendor_settlements', [
            'restaurant_id' => $restaurant->id,
            'gross_amount' => 3000,
            'commission_amount' => 300,
            'net_payable' => 2700,
        ]);

        $this->assertNotNull($order1->fresh()->vendor_settlement_id);
    }
}
