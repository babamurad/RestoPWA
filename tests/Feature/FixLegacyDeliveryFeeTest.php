<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\CourierEarning;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Models\PaymentTransaction;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\VendorSettlement;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Before the fix every delivery order got DELIVERY_FEE_DEFAULT = 500 TMT of delivery.
 */
class FixLegacyDeliveryFeeTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::factory()->create(['delivery_fee' => 15.00, 'commission_percent' => 10]);
    }

    /** Legacy order: 100 TMT of food + the wrong 500 TMT delivery */
    private function legacyOrder(array $attributes = []): Order
    {
        $order = Order::factory()->create($attributes + [
            'vendor_id' => $this->restaurant->id,
            'status' => Order::STATUS_PREPARING,
            'total' => 600.00,
            'delivery_fee' => 500.00,
        ]);
        $order->forceFill(['created_at' => Carbon::parse('2026-10-15 10:00:00'), 'updated_at' => Carbon::parse('2026-10-15 10:00:00')])->saveQuietly();

        return $order->fresh();
    }

    public function test_unpaid_order_gets_the_restaurant_fee(): void
    {
        $order = $this->legacyOrder();
        PaymentTransaction::create([
            'order_id' => $order->id, 'provider' => 'cash', 'type' => PaymentTransaction::TYPE_PAYMENT,
            'status' => PaymentTransaction::STATUS_PENDING, 'amount' => 60000, 'currency' => 'TMT',
        ]);

        $this->artisan('orders:fix-legacy-delivery-fee')
            ->expectsOutputToContain('Исправлены: 1')
            ->assertSuccessful();

        $order->refresh();
        $this->assertEquals(15.00, $order->delivery_fee);
        $this->assertEquals(115.00, $order->total);
        $this->assertSame(11500, PaymentTransaction::where('order_id', $order->id)->value('amount'));
        $this->assertSame('2026-10-15 10:00:00', $order->updated_at->format('Y-m-d H:i:s'));
    }

    public function test_finished_order_without_payment_records_is_only_reported(): void
    {
        // Delivered before payment_transactions existed: shows "unpaid", but the cash was collected
        $delivered = $this->legacyOrder(['status' => Order::STATUS_DELIVERED, 'payment_status' => PaymentStatus::UNPAID]);
        $pickedUp = $this->legacyOrder(['status' => Order::STATUS_COMPLETED, 'payment_status' => PaymentStatus::UNPAID]);

        $this->artisan('orders:fix-legacy-delivery-fee')
            ->expectsOutputToContain('Исправлены: 0')
            ->expectsOutputToContain('Требуют решения вручную (деньги уже прошли): 2')
            ->assertSuccessful();

        $this->assertEquals(500.00, $delivered->fresh()->delivery_fee);
        $this->assertEquals(500.00, $pickedUp->fresh()->delivery_fee);
    }

    public function test_cancelled_order_is_corrected(): void
    {
        $order = $this->legacyOrder(['status' => Order::STATUS_CANCELLED]);

        $this->artisan('orders:fix-legacy-delivery-fee')->assertSuccessful();

        $this->assertEquals(115.00, $order->fresh()->total);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $order = $this->legacyOrder();

        $this->artisan('orders:fix-legacy-delivery-fee --dry-run')
            ->expectsOutputToContain('Будут исправлены: 1')
            ->assertSuccessful();

        $this->assertEquals(500.00, $order->fresh()->delivery_fee);
    }

    public function test_orders_where_money_moved_are_only_reported(): void
    {
        $paid = $this->legacyOrder(['payment_status' => PaymentStatus::PAID]);
        $settled = $this->legacyOrder(['vendor_settlement_id' => VendorSettlement::create([
            'restaurant_id' => $this->restaurant->id, 'period_from' => now()->subWeek(), 'period_to' => now(),
            'gross_amount' => 100.0, 'commission_amount' => 10.0, 'net_payable' => 90.0, 'status' => 'paid',
        ])->id]);
        $credited = $this->legacyOrder(['status' => Order::STATUS_DELIVERING]);
        $courier = Courier::create(['user_id' => User::factory()->create(['role' => UserRole::COURIER])->id]);
        CourierEarning::create(['order_id' => $credited->id, 'courier_id' => $courier->id, 'amount' => 500, 'status' => 'pending']);

        $this->artisan('orders:fix-legacy-delivery-fee')
            ->expectsOutputToContain('Исправлены: 0')
            ->expectsOutputToContain('Требуют решения вручную (деньги уже прошли): 3')
            ->assertSuccessful();

        foreach ([$paid, $settled, $credited] as $order) {
            $this->assertEquals(500.00, $order->fresh()->delivery_fee);
        }
    }

    public function test_new_orders_and_real_500_tariffs_are_left_alone(): void
    {
        $recent = Order::factory()->create(['vendor_id' => $this->restaurant->id, 'total' => 600.00, 'delivery_fee' => 500.00]);
        $expensive = Restaurant::factory()->create(['delivery_fee' => 500.00]);
        $real = $this->legacyOrder(['vendor_id' => $expensive->id]);

        $this->artisan('orders:fix-legacy-delivery-fee')
            ->expectsOutputToContain('Исправлены: 0')
            ->assertSuccessful();

        $this->assertEquals(500.00, $recent->fresh()->delivery_fee);
        $this->assertEquals(500.00, $real->fresh()->delivery_fee);
    }
}
