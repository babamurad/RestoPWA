<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domains\Geo\Services\DeliveryZoneCheckResult;
use App\Domains\Geo\Services\GeoService;
use App\Domains\Menu\Models\Product;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * The delivery fee charged on an order must be the one the customer saw in the
 * cart: Restaurant::delivery_fee (set by the restaurateur in Filament and
 * returned by POST /api/v1/cart/sync).
 */
class OrderDeliveryFeeTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    private Product $product;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::factory()->create([
            'is_active' => true,
            'delivery_fee' => 12.00,
            'min_order' => 0,
        ]);
        $this->restaurant->update(['vendor_id' => $this->restaurant->id]);

        $this->product = Product::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'price' => 100.00,
            'is_available' => true,
        ]);

        $this->user = User::factory()->create(['phone' => '+99361234567']);

        // Real calculateDeliveryFee(), only the PostGIS zone lookup is stubbed.
        $geo = Mockery::mock(GeoService::class)->makePartial();
        $geo->shouldReceive('checkDeliveryZone')
            ->andReturn(new DeliveryZoneCheckResult('inside', true, 'Allowed'));
        $this->instance(GeoService::class, $geo);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'vendor_id' => $this->restaurant->id,
            'delivery_type' => 'delivery',
            'items' => [[
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'quantity' => 1,
                'unit_price' => 10000,
                'total_price' => 10000,
                'modifiers' => [],
            ]],
            'total' => 11200,
            'delivery_fee' => 1200,
            'customer_name' => 'Тест',
            'customer_phone' => '+99361234567',
            'address' => ['lat' => 39.0886, 'lon' => 63.5593, 'address' => 'Тестовая 1'],
            'payment_method' => 'cash',
        ], $overrides);
    }

    public function test_delivery_fee_matches_the_restaurant_fee_shown_in_cart(): void
    {
        $cart = $this->actingAs($this->user)->postJson('/api/v1/cart/sync', [
            'vendor_id' => $this->restaurant->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'price' => 100.00]],
        ]);
        $cart->assertOk();
        $this->assertEquals(12.00, $cart->json('data.delivery_fee'));

        $response = $this->actingAs($this->user)->postJson('/api/v1/orders', $this->payload());

        $response->assertStatus(201);
        $order = Order::find($response->json('data.order_id'));
        $this->assertEquals(12.00, $order->delivery_fee);
        $this->assertEquals(112.00, $order->total);
    }

    public function test_free_delivery_restaurant_charges_no_delivery_fee(): void
    {
        $this->restaurant->update(['delivery_fee' => 0]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/orders', $this->payload([
            'total' => 10000,
            'delivery_fee' => 0,
        ]));

        $response->assertStatus(201);
        $order = Order::find($response->json('data.order_id'));
        $this->assertEquals(0.00, $order->delivery_fee);
        $this->assertEquals(100.00, $order->total);
    }

    public function test_client_sent_pickup_type_does_not_waive_delivery_fee(): void
    {
        // delivery_type is not persisted on the order, so the order is still
        // dispatched to the address as a delivery and must pay for it.
        $response = $this->actingAs($this->user)->postJson('/api/v1/orders', $this->payload([
            'delivery_type' => 'pickup',
            'total' => 10000,
            'delivery_fee' => 0,
        ]));

        $response->assertStatus(201);
        $order = Order::find($response->json('data.order_id'));
        $this->assertEquals(12.00, $order->delivery_fee);
        $this->assertEquals(112.00, $order->total);
    }
}
