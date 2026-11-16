<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Geo\Services\DeliveryZoneCheckResult;
use App\Domains\Geo\Services\GeoService;
use App\Domains\Menu\Models\Product;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Database\Seeders\RestaurantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * Money: the application works in currency units (TMT), the database and the
 * order API in whole minor units (tyiyn). The type of a number never changes its meaning.
 */
class MoneyUnitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_integer_float_and_string_amounts_are_stored_the_same(): void
    {
        $restaurant = Restaurant::factory()->create();

        foreach ([20, 20.0, '20', '20.00'] as $amount) {
            $product = Product::factory()->create(['vendor_id' => $restaurant->id, 'price' => $amount]);

            $this->assertSame(2000, (int) DB::table('products')->where('id', $product->id)->value('price'), var_export($amount, true));
            $this->assertSame(20.0, $product->fresh()->price);
        }
    }

    public function test_restaurant_settings_saved_as_whole_numbers_keep_their_value(): void
    {
        // A form may hand over "15" as an integer: it must stay 15 TMT, not become 0.15
        $restaurant = Restaurant::factory()->create();
        $restaurant->update(['min_order' => 15, 'delivery_fee' => 10]);

        $this->assertSame(15.0, $restaurant->fresh()->min_order);
        $this->assertSame(10.0, $restaurant->fresh()->delivery_fee);
        $this->assertSame(1500, (int) DB::table('restaurants')->where('id', $restaurant->id)->value('min_order'));
    }

    public function test_order_api_still_counts_in_minor_units(): void
    {
        $restaurant = Restaurant::factory()->create(['is_active' => true, 'min_order' => 10.00]);
        $product = Product::factory()->create(['vendor_id' => $restaurant->id, 'price' => 12.50, 'is_available' => true]);
        $user = User::factory()->create();

        $this->instance(GeoService::class, Mockery::mock(GeoService::class, function ($mock) {
            $mock->shouldReceive('checkDeliveryZone')->andReturn(new DeliveryZoneCheckResult('inside', true, 'Allowed'));
            $mock->shouldReceive('calculateDeliveryFee')->andReturn(7.0);
        }));

        $response = $this->actingAs($user)->postJson('/api/v1/orders', [
            'vendor_id' => $restaurant->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'customer_name' => 'Test User',
            'customer_phone' => '+99312345678',
            'address' => ['lat' => 1.0, 'lon' => 1.0, 'address' => 'Test Street 1'],
            'payment_method' => 'cash',
        ])->assertStatus(201);

        $order = Order::findOrFail($response->json('data.order_id'));

        // 2 × 12.50 + 7.00 delivery
        $this->assertSame(32.0, $order->total);
        $this->assertSame(7.0, $order->delivery_fee);
        $this->assertSame(25.0, $order->foodTotal());
        $this->assertSame(3200, (int) DB::table('orders')->where('id', $order->id)->value('total'));
        $this->assertSame(700, (int) DB::table('orders')->where('id', $order->id)->value('delivery_fee'));
        // Item prices inside the order stay in minor units, as the API contract says
        $this->assertSame(1250, $order->items[0]['unit_price']);
        $this->assertSame(2500, $order->items[0]['total_price']);
    }

    public function test_seeded_menu_keeps_its_prices(): void
    {
        $this->seed(RestaurantSeeder::class);

        // Seed data is written in tyiyn: 2499 is 24.99 TMT
        $set = Product::withoutGlobalScopes()->where('name', 'Сет "Сакура"')->firstOrFail();
        $this->assertSame(24.99, $set->price);
        $this->assertSame(2499, (int) DB::table('products')->where('id', $set->id)->value('price'));

        $sushi = Restaurant::withoutGlobalScopes()->findOrFail($set->vendor_id);
        $this->assertSame(10.0, $sushi->min_order);
        $this->assertSame(1.49, $sushi->delivery_fee);
    }
}
