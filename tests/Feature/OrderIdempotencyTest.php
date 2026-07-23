<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Geo\Services\GeoService;
use App\Domains\Geo\Services\DeliveryZoneCheckResult;
use App\Domains\Menu\Models\Product;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class OrderIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected Restaurant $restaurant;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::factory()->create([
            'is_active' => true,
            'delivery_fee' => 5.00,
            'min_order' => 10.00,
        ]);
        $this->restaurant->update(['vendor_id' => $this->restaurant->id]);

        $this->product = Product::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'price' => 1000,
            'is_available' => true,
        ]);

        $this->instance(GeoService::class, \Mockery::mock(GeoService::class, function ($mock) {
            $mock->shouldReceive('checkDeliveryZone')->andReturn(new DeliveryZoneCheckResult('inside', true, 'Allowed'));
            $mock->shouldReceive('geocodeWithFallback')->andReturn(null);
        }));
    }

    public function test_order_submission_is_idempotent(): void
    {
        $user = User::factory()->create(['phone' => '+99361234567']);
        $idempotencyKey = (string) Str::uuid();

        $payload = [
            'vendor_id' => $this->restaurant->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'quantity' => 2,
                    'unit_price' => 1000,
                    'total_price' => 2000,
                ]
            ],
            'total' => 2000,
            'payment_method' => 'cash',
            'customer_name' => 'John Doe',
            'customer_phone' => '+99361234567',
            'address' => [
                'address' => 'Main St 10',
                'lat' => 39.0886,
                'lon' => 63.5593,
            ],
        ];

        // First request
        $response1 = $this->actingAs($user)
            ->withHeaders([
                'X-Idempotency-Key' => $idempotencyKey,
                'X-Vendor-ID' => $this->restaurant->id,
            ])
            ->postJson('/api/v1/orders', $payload);

        $response1->assertStatus(201);
        $orderId = $response1->json('data.order_id');
        $this->assertNotNull($orderId);

        // Second request with SAME key
        $response2 = $this->actingAs($user)
            ->withHeaders([
                'X-Idempotency-Key' => $idempotencyKey,
                'X-Vendor-ID' => $this->restaurant->id,
            ])
            ->postJson('/api/v1/orders', $payload);

        $response2->assertStatus(200);
        $response2->assertJson([
            'success' => true,
            'data' => [
                'order_id' => $orderId,
                'is_duplicate' => true,
            ],
        ]);

        // Check that only ONE order was created in DB
        $this->assertEquals(1, Order::where('idempotency_key', $idempotencyKey)->count());
    }

    public function test_order_submission_without_key_creates_new_orders(): void
    {
        $user = User::factory()->create(['phone' => '+99361234567']);

        $payload = [
            'vendor_id' => $this->restaurant->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'total_price' => 1000,
                ]
            ],
            'total' => 1000,
            'payment_method' => 'cash',
            'customer_name' => 'John Doe',
            'customer_phone' => '+99361234567',
            'address' => [
                'address' => 'Main St 10',
                'lat' => 39.0886,
                'lon' => 63.5593,
            ],
        ];

        // First request
        $response1 = $this->actingAs($user)
            ->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson('/api/v1/orders', $payload);
        $response1->assertStatus(201);

        // Second request
        $response2 = $this->actingAs($user)
            ->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson('/api/v1/orders', $payload);
        $response2->assertStatus(201);

        $this->assertNotEquals($response1->json('data.order_id'), $response2->json('data.order_id'));
        $this->assertEquals(2, Order::count());
    }

    public function test_different_users_with_same_key_do_not_interfere_on_logic_level(): void
    {
        $user1 = User::factory()->create(['phone' => '+99361234567']);
        $user2 = User::factory()->create(['phone' => '+99361234568']);
        $idempotencyKey = (string) Str::uuid();

        $payload = [
            'vendor_id' => $this->restaurant->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'total_price' => 1000,
                ]
            ],
            'total' => 1000,
            'payment_method' => 'cash',
            'customer_name' => 'John Doe',
            'customer_phone' => '+99361234567',
            'address' => [
                'address' => 'Main St 10',
                'lat' => 39.0886,
                'lon' => 63.5593,
            ],
        ];

        // User 1 creates order
        $this->actingAs($user1)
            ->withHeaders([
                'X-Idempotency-Key' => $idempotencyKey,
                'X-Vendor-ID' => $this->restaurant->id,
            ])
            ->postJson('/api/v1/orders', $payload)
            ->assertStatus(201);

        // User 2 tries to use SAME key
        // This should probably fail at DB level because of UNIQUE constraint on idempotency_key
        $response = $this->actingAs($user2)
            ->withHeaders([
                'X-Idempotency-Key' => $idempotencyKey,
                'X-Vendor-ID' => $this->restaurant->id,
            ])
            ->postJson('/api/v1/orders', array_merge($payload, [
                'customer_name' => 'Jane Doe',
                'customer_phone' => '+99361234568',
                'address' => [
                    'address' => 'Main St 10',
                    'lat' => 39.0886,
                    'lon' => 63.5593,
                ]
            ]));

        $response->assertStatus(500);
    }
}
