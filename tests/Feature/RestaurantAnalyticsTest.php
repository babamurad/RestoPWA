<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Analytics\Data\AnalyticsPeriod;
use App\Domains\Analytics\Services\RestaurantAnalytics;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\UserRole;
use App\Filament\Vendor\Analytics\Widgets\SalesOverview;
use App\Filament\Vendor\Analytics\Widgets\TopProducts;
use App\Filament\Vendor\Pages\Analytics;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RestaurantAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    private RestaurantAnalytics $analytics;

    protected function setUp(): void
    {
        parent::setUp();

        // 2026-10-15 12:00 in Ashgabat (UTC+5)
        $this->travelTo(CarbonImmutable::parse('2026-10-15 07:00:00', 'UTC'));
        $this->restaurant = Restaurant::factory()->create(['timezone' => 'Asia/Ashgabat']);
        $this->analytics = new RestaurantAnalytics;
    }

    /** $localTime is Ashgabat time; money in TMT, item prices in minor units like real orders */
    private function order(string $localTime, string $status = Order::STATUS_DELIVERED, float $total = 110, float $delivery = 10, array $attributes = []): Order
    {
        $order = Order::factory()->create($attributes + [
            'vendor_id' => $this->restaurant->id,
            'status' => $status,
            'total' => $total,
            'delivery_fee' => $delivery,
            'items' => [['product_id' => 'plov', 'product_name' => 'Плов', 'quantity' => 2, 'unit_price' => 5000, 'total_price' => 10000]],
        ]);
        $order->forceFill(['created_at' => CarbonImmutable::parse($localTime, 'Asia/Ashgabat')->utc()])->saveQuietly();

        return $order;
    }

    private function week(): AnalyticsPeriod
    {
        return AnalyticsPeriod::preset('7_days', 'Asia/Ashgabat');
    }

    public function test_presets_use_the_restaurant_local_calendar(): void
    {
        $now = CarbonImmutable::parse('2026-10-15 21:00:00', 'UTC'); // already Oct 16 in Ashgabat

        $today = AnalyticsPeriod::preset('today', 'Asia/Ashgabat', $now);
        $this->assertSame('2026-10-16 00:00:00', $today->from->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-15 19:00:00', $today->utcBounds()[0]->format('Y-m-d H:i:s'));

        $lastMonth = AnalyticsPeriod::preset('last_month', 'Asia/Ashgabat', $now);
        $this->assertSame('01.09.2026 — 30.09.2026', $lastMonth->label());

        $week = AnalyticsPeriod::preset('7_days', 'Asia/Ashgabat', $now);
        $this->assertSame(7, $week->days());
        $this->assertSame('03.10.2026 — 09.10.2026', $week->previous()->label());
    }

    public function test_custom_period_is_ordered_and_capped(): void
    {
        $this->assertSame('15.10.2026', AnalyticsPeriod::fromFilters(['period' => 'custom', 'from' => '2026-10-15'], 'Asia/Ashgabat')->label());
        $this->assertSame(7, AnalyticsPeriod::fromFilters(['period' => 'bogus'], 'Asia/Ashgabat')->days());
        $this->assertSame(7, AnalyticsPeriod::fromFilters(['period' => 'custom'], 'Asia/Ashgabat')->days());

        $period = AnalyticsPeriod::between('2026-10-10', '2026-10-01', 'Asia/Ashgabat');
        $this->assertSame('01.10.2026 — 10.10.2026', $period->label());

        $long = AnalyticsPeriod::between('2020-01-01', '2026-10-15', 'Asia/Ashgabat');
        $this->assertSame(AnalyticsPeriod::MAX_DAYS, $long->days());
    }

    public function test_summary_counts_only_finished_orders_as_sales(): void
    {
        $this->order('2026-10-14 13:00', total: 110, delivery: 10, attributes: ['commission_amount' => 11.0]);
        $this->order('2026-10-15 10:00', status: 'completed', total: 60, delivery: 0, attributes: ['delivery_type' => Order::DELIVERY_TYPE_PICKUP]);
        $this->order('2026-10-15 11:00', status: Order::STATUS_CANCELLED, total: 500);
        $this->order('2026-10-15 11:30', status: Order::STATUS_PREPARING, total: 300);
        $this->order('2026-10-01 12:00', total: 999); // before the period
        Order::factory()->create(['status' => Order::STATUS_DELIVERED, 'total' => 777]); // another restaurant

        $summary = $this->analytics->summary($this->restaurant->id, $this->week());

        $this->assertSame(4, $summary['orders']);
        $this->assertSame(2, $summary['completed']);
        $this->assertSame(1, $summary['cancelled']);
        $this->assertSame(1, $summary['in_progress']);
        $this->assertSame(25.0, $summary['cancel_rate']);
        $this->assertEquals(170, $summary['gross']);
        $this->assertEquals(10, $summary['delivery_fees']);
        $this->assertEquals(160, $summary['revenue']); // delivery goes to couriers
        $this->assertEquals(11, $summary['commission']);
        $this->assertEquals(149, $summary['payout']);
        $this->assertEquals(80, $summary['average_check']);
        $this->assertSame(1, $summary['delivery_orders']);
        $this->assertSame(1, $summary['pickup_orders']);
    }

    public function test_daily_series_groups_by_local_day_and_fills_gaps(): void
    {
        $this->order('2026-10-14 23:30'); // 18:30 UTC — still Oct 14 locally
        $this->order('2026-10-15 00:30'); // 19:30 UTC on Oct 14 — Oct 15 locally

        $daily = collect($this->analytics->daily($this->restaurant->id, $this->week()))->keyBy('date');

        $this->assertCount(7, $daily);
        $this->assertSame(1, $daily['2026-10-14']['orders']);
        $this->assertSame(1, $daily['2026-10-15']['orders']);
        $this->assertEquals(100, $daily['2026-10-15']['revenue']);
        $this->assertSame(0, $daily['2026-10-09']['orders']);
    }

    public function test_hourly_load_ignores_cancelled_orders(): void
    {
        $this->order('2026-10-15 09:10');
        $this->order('2026-10-14 09:50', status: Order::STATUS_PREPARING);
        $this->order('2026-10-15 09:20', status: Order::STATUS_CANCELLED);
        $this->order('2026-10-15 19:00');

        $hours = $this->analytics->hourly($this->restaurant->id, $this->week());

        $this->assertSame(2, $hours[9]);
        $this->assertSame(1, $hours[19]);
        $this->assertSame(3, array_sum($hours));
    }

    public function test_top_products_sum_quantity_and_revenue(): void
    {
        $this->order('2026-10-15 10:00');
        $this->order('2026-10-15 11:00', attributes: ['items' => [
            ['product_id' => 'plov', 'product_name' => 'Плов', 'quantity' => 1, 'unit_price' => 5000, 'total_price' => 5000],
            ['product_id' => 'chai', 'product_name' => 'Чай', 'quantity' => 1, 'unit_price' => 1000, 'total_price' => 1000],
        ]]);
        $this->order('2026-10-15 12:00', status: Order::STATUS_CANCELLED);

        $top = $this->analytics->topProducts($this->restaurant->id, $this->week());

        $this->assertSame('Плов', $top[0]['name']);
        $this->assertSame(3, $top[0]['quantity']);
        $this->assertEquals(150, $top[0]['revenue']);
        $this->assertSame('Чай', $top[1]['name']);
    }

    public function test_change_against_previous_period(): void
    {
        $this->assertSame(50.0, RestaurantAnalytics::change(150, 100));
        $this->assertSame(-25.0, RestaurantAnalytics::change(75, 100));
        $this->assertNull(RestaurantAnalytics::change(10, 0));
    }

    public function test_owner_sees_analytics_of_own_restaurant_only(): void
    {
        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $this->restaurant->update(['owner_id' => $owner->id]);
        $this->order('2026-10-15 10:00', total: 1234, delivery: 0);
        Order::factory()->create(['status' => Order::STATUS_DELIVERED, 'total' => 98765]);

        $this->actingAs($owner);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        Filament::setTenant($this->restaurant);
        Filament::bootCurrentPanel();

        $this->get(Analytics::getUrl(tenant: $this->restaurant))->assertOk()->assertSee('Аналитика');

        Livewire::test(SalesOverview::class, ['pageFilters' => ['period' => '7_days']])
            ->assertSee('1 234 TMT')
            ->assertDontSee('98 765');

        Livewire::test(TopProducts::class, ['pageFilters' => ['period' => 'custom', 'from' => '2026-10-15', 'to' => '2026-10-15']])
            ->assertSee('Плов');

        Livewire::test(TopProducts::class, ['pageFilters' => ['period' => 'yesterday']])
            ->assertSee('выполненных заказов нет');
    }

    public function test_owner_exports_orders_of_the_period_to_csv(): void
    {
        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $this->restaurant->update(['owner_id' => $owner->id]);
        $this->order('2026-10-15 10:00', total: 110, delivery: 10, attributes: ['commission_amount' => 10.0]);
        $this->order('2026-10-14 09:00', status: Order::STATUS_CANCELLED);
        $this->order('2026-09-01 09:00'); // outside the period

        $this->actingAs($owner);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        Filament::setTenant($this->restaurant);
        Filament::bootCurrentPanel();

        $response = Livewire::test(Analytics::class)->set('filters', ['period' => '7_days'])->instance()->exportCsv();

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $lines = array_values(array_filter(explode("\n", trim(substr($csv, 3)))));
        $this->assertCount(3, $lines);
        $this->assertStringContainsString('Дата;"ID заказа";Статус', $lines[0]);
        $this->assertStringContainsString('14.10.2026 09:00', $lines[1]);
        $this->assertStringContainsString('Отменён', $lines[1]);
        $this->assertStringContainsString('15.10.2026 10:00', $lines[2]);
        $this->assertStringEndsWith('110,00;10,00;100,00;10,00;90,00', $lines[2]);
        $this->assertStringContainsString('orders_2026-10-09_2026-10-15.csv', $response->headers->get('Content-Disposition'));
    }

    public function test_custom_dates_are_limited_by_the_restaurant_today(): void
    {
        // 01:00 on Oct 16 in Ashgabat, still Oct 15 in UTC
        $this->travelTo(CarbonImmutable::parse('2026-10-15 20:00:00', 'UTC'));
        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $this->restaurant->update(['owner_id' => $owner->id]);

        $this->actingAs($owner);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        Filament::setTenant($this->restaurant);
        Filament::bootCurrentPanel();

        $this->assertSame('2026-10-16', Livewire::test(Analytics::class)->instance()->restaurantToday());
    }

    public function test_page_is_not_available_for_a_foreign_restaurant(): void
    {
        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        Restaurant::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(Analytics::getUrl(panel: 'vendor', tenant: $this->restaurant))
            ->assertNotFound();
    }
}
