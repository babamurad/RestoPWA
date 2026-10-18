<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Analytics\Data\AnalyticsPeriod;
use App\Domains\Analytics\Services\PlatformAnalytics;
use App\Domains\Logistics\Models\Courier;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderRejection;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\OrderRejectReason;
use App\Enums\UserRole;
use App\Filament\Admin\Analytics\Widgets\PlatformOverview;
use App\Filament\Admin\Analytics\Widgets\RejectionReasons;
use App\Filament\Admin\Analytics\Widgets\RestaurantsTable;
use App\Filament\Pages\Analytics;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $plov;

    private Restaurant $pizza;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-15 07:00:00', 'UTC')); // 12:00 in Ashgabat
        $this->plov = Restaurant::factory()->create(['name' => 'Плов-центр']);
        $this->pizza = Restaurant::factory()->create(['name' => 'Пицца Ашхабад']);
    }

    private function order(Restaurant $restaurant, string $status, float $total, float $delivery = 0, array $attributes = []): Order
    {
        return Order::factory()->create($attributes + [
            'vendor_id' => $restaurant->id,
            'status' => $status,
            'total' => $total,
            'delivery_fee' => $delivery,
        ]);
    }

    private function week(): AnalyticsPeriod
    {
        return AnalyticsPeriod::preset('7_days', Analytics::TIMEZONE);
    }

    public function test_summary_across_restaurants(): void
    {
        $courier = Courier::create(['user_id' => User::factory()->create(['role' => UserRole::COURIER])->id, 'status' => Courier::STATUS_AVAILABLE]);
        $this->order($this->plov, Order::STATUS_DELIVERED, 110, 10, ['courier_id' => $courier->id, 'commission_amount' => 10.0]);
        $this->order($this->pizza, Order::STATUS_COMPLETED, 50, 0, ['delivery_type' => Order::DELIVERY_TYPE_PICKUP, 'commission_amount' => 5.0]);
        $this->order($this->pizza, Order::STATUS_CANCELLED, 999);
        $this->order($this->pizza, Order::STATUS_PREPARING, 70);
        OrderRejection::record(OrderRejectReason::OUTSIDE_DELIVERY_ZONE, $this->plov->id, null);

        $summary = app(PlatformAnalytics::class)->summary($this->week());

        $this->assertSame(4, $summary['orders']);
        $this->assertSame(2, $summary['finished']);
        $this->assertSame(1, $summary['cancelled']);
        $this->assertEquals(160, $summary['gmv']);
        $this->assertEquals(150, $summary['food']);
        $this->assertEquals(10, $summary['delivery_fees']);
        $this->assertEquals(15, $summary['commission']);
        $this->assertEquals(80, $summary['average_check']);
        $this->assertSame(2, $summary['active_restaurants']);
        $this->assertSame(1, $summary['active_couriers']);
        $this->assertSame(1, $summary['rejections']);

        $live = app(PlatformAnalytics::class)->rightNow();
        $this->assertSame(1, $live['couriers_online']);
    }

    public function test_restaurants_are_ranked_by_turnover(): void
    {
        $this->order($this->plov, Order::STATUS_DELIVERED, 300, 10);
        $this->order($this->pizza, Order::STATUS_DELIVERED, 100, 10);
        $this->order($this->pizza, Order::STATUS_CANCELLED, 100);

        $rows = app(PlatformAnalytics::class)->restaurants($this->week());

        $this->assertSame(['Плов-центр', 'Пицца Ашхабад'], array_column($rows, 'name'));
        $this->assertEquals(50.0, $rows[1]['cancel_rate']);
    }

    public function test_refused_orders_are_recorded_with_their_reason(): void
    {
        $customer = User::factory()->create(['phone' => '+99361234567']);
        $this->plov->update(['is_active' => false]);

        $this->actingAs($customer)->postJson('/api/v1/orders', [
            'vendor_id' => $this->plov->id,
            'items' => [['product_id' => fake()->uuid(), 'product_name' => 'Плов', 'quantity' => 1, 'unit_price' => 5000, 'total_price' => 5000]],
            'total' => 5000,
            'delivery_fee' => 0,
            'customer_name' => 'Тест',
            'customer_phone' => '+99361234567',
            'address' => ['lat' => 37.95, 'lon' => 58.38, 'address' => 'Тестовая 1'],
            'payment_method' => 'cash',
        ])->assertStatus(422);

        $rejection = OrderRejection::sole();
        $this->assertSame($this->plov->id, $rejection->vendor_id);
        $this->assertSame($customer->id, $rejection->user_id);

        $reasons = app(PlatformAnalytics::class)->rejectionReasons($this->week());
        $this->assertSame($rejection->reason->value, $reasons[0]['reason']);
        $this->assertSame(100.0, $reasons[0]['share']);
    }

    public function test_rejection_with_unknown_vendor_is_still_recorded(): void
    {
        OrderRejection::record(OrderRejectReason::VALIDATION, 'not-a-uuid', null);

        $this->assertNull(OrderRejection::sole()->vendor_id);
    }

    public function test_admin_sees_the_page_and_others_do_not(): void
    {
        $this->order($this->plov, Order::STATUS_DELIVERED, 1234, 0);
        OrderRejection::record(OrderRejectReason::RESTAURANT_CLOSED, $this->plov->id, null);

        $this->actingAs(User::factory()->create(['role' => UserRole::RESTAURATEUR]))
            ->get('/admin/analytics')
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->get('/admin/analytics')->assertOk()->assertSee('Аналитика');

        Livewire::test(PlatformOverview::class, ['pageFilters' => ['period' => '7_days']])->assertSee('1 234 TMT');
        Livewire::test(RestaurantsTable::class, ['pageFilters' => ['period' => 'today']])->assertSee('Плов-центр');
        Livewire::test(RejectionReasons::class, ['pageFilters' => ['period' => 'today']])->assertSee('Ресторан закрыт');
        Livewire::test(RejectionReasons::class, ['pageFilters' => ['period' => 'yesterday']])->assertSee('отказов не было');
    }
}
