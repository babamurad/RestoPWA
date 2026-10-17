<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domains\Geo\Services\DeliveryZoneCheckResult;
use App\Domains\Geo\Services\GeoService;
use App\Domains\Logistics\Models\Courier;
use App\Domains\Menu\Models\Product;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Pickup orders: stored with delivery_type=pickup, no delivery fee,
 * no delivery-zone check, and never offered to or assigned to couriers.
 */
class OrderPickupTest extends TestCase
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

        $this->stubZone(new DeliveryZoneCheckResult('inside', true, 'Allowed'));
    }

    private function stubZone(DeliveryZoneCheckResult $result): void
    {
        // Real calculateDeliveryFee(), only the PostGIS zone lookup is stubbed.
        $geo = Mockery::mock(GeoService::class)->makePartial();
        $geo->shouldReceive('checkDeliveryZone')->andReturn($result);
        $this->instance(GeoService::class, $geo);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'vendor_id' => $this->restaurant->id,
            'delivery_type' => 'pickup',
            'items' => [[
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'quantity' => 1,
                'unit_price' => 10000,
                'total_price' => 10000,
                'modifiers' => [],
            ]],
            'total' => 10000,
            'delivery_fee' => 0,
            'customer_name' => 'Тест',
            'customer_phone' => '+99361234567',
            // Same fallback the PWA sends for pickup
            'address' => ['lat' => 39.0886, 'lon' => 63.5593, 'address' => 'Самовывоз'],
            'payment_method' => 'cash',
        ], $overrides);
    }

    private function makeCourier(): Courier
    {
        $user = User::factory()->create(['role' => UserRole::COURIER]);

        return Courier::create(['user_id' => $user->id]);
    }

    public function test_pickup_order_is_stored_as_pickup_without_delivery_fee(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/orders', $this->payload());

        $response->assertStatus(201);
        $order = Order::find($response->json('data.order_id'));
        $this->assertSame(Order::DELIVERY_TYPE_PICKUP, $order->delivery_type);
        $this->assertEquals(0.00, $order->delivery_fee);
        $this->assertEquals(100.00, $order->total);
    }

    public function test_pickup_order_skips_delivery_zone_check(): void
    {
        $this->stubZone(new DeliveryZoneCheckResult('outside', false, 'Outside'));

        $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->payload())
            ->assertStatus(201);
    }

    public function test_delivery_order_outside_zone_is_still_rejected(): void
    {
        $this->stubZone(new DeliveryZoneCheckResult('outside', false, 'Outside'));

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->payload(['delivery_type' => 'delivery']));

        $this->assertNotSame(201, $response->status());
        $response->assertJsonPath('reason', 'outside_delivery_zone');
    }

    public function test_order_without_delivery_type_defaults_to_delivery(): void
    {
        $payload = $this->payload(['total' => 11200, 'delivery_fee' => 1200]);
        unset($payload['delivery_type']);

        $response = $this->actingAs($this->user)->postJson('/api/v1/orders', $payload);

        $response->assertStatus(201);
        $order = Order::find($response->json('data.order_id'));
        $this->assertSame(Order::DELIVERY_TYPE_DELIVERY, $order->delivery_type);
        $this->assertEquals(12.00, $order->delivery_fee);
    }

    public function test_unknown_delivery_type_is_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->payload(['delivery_type' => 'drone']));

        $response->assertStatus(400);
        $this->assertSame(0, Order::count());
    }

    public function test_pickup_order_is_not_offered_to_couriers(): void
    {
        $courier = $this->makeCourier();
        $pickup = Order::factory()->create([
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'delivery_type' => Order::DELIVERY_TYPE_PICKUP,
        ]);
        $delivery = Order::factory()->create(['status' => Order::STATUS_READY_FOR_PICKUP]);

        $response = $this->actingAs($courier->user)->getJson('/api/v1/courier/orders');

        $response->assertOk();
        $ids = collect($response->json('data.available'))->pluck('id');
        $this->assertTrue($ids->contains($delivery->id));
        $this->assertFalse($ids->contains($pickup->id));
    }

    public function test_courier_cannot_accept_pickup_order(): void
    {
        $courier = $this->makeCourier();
        $order = Order::factory()->create([
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'delivery_type' => Order::DELIVERY_TYPE_PICKUP,
        ]);

        $this->actingAs($courier->user)
            ->postJson("/api/v1/courier/orders/{$order->id}/accept")
            ->assertStatus(422);

        $this->assertNull($order->fresh()->courier_id);
    }

    public function test_courier_cannot_be_assigned_to_pickup_order(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $courier = $this->makeCourier();
        $order = Order::factory()->create([
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'delivery_type' => Order::DELIVERY_TYPE_PICKUP,
        ]);

        $this->actingAs($admin)
            ->postJson("/api/v1/orders/{$order->id}/assign", ['courier_id' => $courier->id])
            ->assertStatus(422);

        $this->assertNull($order->fresh()->courier_id);
    }
}
