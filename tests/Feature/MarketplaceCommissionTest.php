<?php

namespace Tests\Feature;

use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\VendorSettlement;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Commission = restaurant percent × (order total − delivery fee), charged on
 * delivered and picked-up orders; payouts are for food, delivery goes to couriers.
 */
class MarketplaceCommissionTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::factory()->create(['commission_percent' => 10.00]);
    }

    private function order(array $attributes): Order
    {
        return Order::factory()->create(['vendor_id' => $this->restaurant->id] + $attributes);
    }

    public function test_commission_excludes_the_delivery_fee(): void
    {
        $order = $this->order(['status' => Order::STATUS_DELIVERING, 'total' => 145.50, 'delivery_fee' => 20.00]);

        $order->update(['status' => Order::STATUS_DELIVERED]);

        // 10% of 125.50, kept to the tyiyn (not rounded to whole manats)
        $this->assertEquals(12.55, $order->fresh()->commission_amount);
    }

    public function test_picked_up_orders_pay_commission_too(): void
    {
        $order = $this->order([
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'delivery_type' => Order::DELIVERY_TYPE_PICKUP,
            'total' => 80.00,
            'delivery_fee' => 0.0,
        ]);

        $order->update(['status' => Order::STATUS_COMPLETED]);

        $this->assertEquals(8.00, $order->fresh()->commission_amount);
    }

    public function test_cancelled_order_has_no_commission(): void
    {
        $order = $this->order(['status' => Order::STATUS_PREPARING, 'total' => 100.00]);

        $order->update(['status' => Order::STATUS_CANCELLED]);

        $this->assertEquals(0, $order->fresh()->commission_amount);
    }

    public function test_delivery_order_closed_on_the_kanban_is_not_charged_or_paid_out(): void
    {
        // Dragged to "completed" past the courier: nobody delivered it, no courier was paid
        $order = $this->order(['status' => Order::STATUS_READY_FOR_PICKUP, 'total' => 110.00, 'delivery_fee' => 10.00]);
        $order->update(['status' => Order::STATUS_COMPLETED]);
        $order->forceFill(['updated_at' => Carbon::now()->subDay()])->saveQuietly();

        $this->assertEquals(0, $order->fresh()->commission_amount);
        $this->assertFalse($order->fresh()->isFinished());

        $this->artisan('settlements:generate')->assertSuccessful();
        $this->assertNull($order->fresh()->vendor_settlement_id);
    }

    public function test_settlement_pays_for_food_of_delivered_and_picked_up_orders(): void
    {
        $delivered = $this->order([
            'status' => Order::STATUS_DELIVERED,
            'total' => 110.00, 'delivery_fee' => 10.00, 'commission_amount' => 10.00,
            'updated_at' => Carbon::now()->subDays(2),
        ]);
        $pickedUp = $this->order([
            'status' => Order::STATUS_COMPLETED, 'delivery_type' => Order::DELIVERY_TYPE_PICKUP,
            'total' => 50.00, 'delivery_fee' => 0.0, 'commission_amount' => 5.00,
            'updated_at' => Carbon::now()->subDays(2),
        ]);
        $this->order(['status' => Order::STATUS_CANCELLED, 'total' => 999.00, 'updated_at' => Carbon::now()->subDays(2)]);

        $this->artisan('settlements:generate')->assertSuccessful();

        $settlement = VendorSettlement::where('restaurant_id', $this->restaurant->id)->sole();
        $this->assertEquals(150.00, $settlement->gross_amount); // food only, no delivery fee
        $this->assertEquals(15.00, $settlement->commission_amount);
        $this->assertEquals(135.00, $settlement->net_payable);
        $this->assertSame($settlement->id, $delivered->fresh()->vendor_settlement_id);
        $this->assertSame($settlement->id, $pickedUp->fresh()->vendor_settlement_id);
    }

    public function test_recalculation_fixes_unsettled_orders_only(): void
    {
        // Old rule: 10% of the total with delivery, rounded to manats; pickups had none
        $open = $this->order(['status' => Order::STATUS_DELIVERED, 'total' => 145.50, 'delivery_fee' => 20.00, 'commission_amount' => 15.00]);
        $pickup = $this->order(['status' => Order::STATUS_COMPLETED, 'delivery_type' => Order::DELIVERY_TYPE_PICKUP, 'total' => 80.00, 'delivery_fee' => 0.0, 'commission_amount' => 0.0]);
        $settledId = VendorSettlement::create([
            'restaurant_id' => $this->restaurant->id, 'period_from' => now()->subWeek(), 'period_to' => now(),
            'gross_amount' => 110.00, 'commission_amount' => 11.00, 'net_payable' => 99.00, 'status' => 'paid',
        ])->id;
        $settled = $this->order(['status' => Order::STATUS_DELIVERED, 'total' => 110.00, 'delivery_fee' => 10.00, 'commission_amount' => 11.00, 'vendor_settlement_id' => $settledId]);
        $completedAt = Carbon::now()->subWeeks(3)->startOfSecond();
        $open->forceFill(['updated_at' => $completedAt])->saveQuietly();

        $this->artisan('orders:recalculate-commission --dry-run')
            ->expectsOutputToContain('Would update 2 orders')
            ->assertSuccessful();
        $this->assertEquals(15.00, $open->fresh()->commission_amount);

        $this->artisan('orders:recalculate-commission')
            ->expectsOutputToContain('Updated 2 orders: commission 15.00 → 20.55 TMT')
            ->assertSuccessful();

        $this->assertEquals(12.55, $open->fresh()->commission_amount);
        // updated_at decides the payout period: the old order must stay in its own week
        $this->assertEquals($completedAt, $open->fresh()->updated_at);
        $this->assertEquals(8.00, $pickup->fresh()->commission_amount);
        $this->assertEquals(11.00, $settled->fresh()->commission_amount); // already paid out
    }
}
