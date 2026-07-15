<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domains\Menu\Models\Product;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;
use App\Enums\OrderRejectReason;
use App\Domains\Geo\Services\GeoService;
use App\Domains\Geo\Services\DeliveryZoneCheckResult;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;
    private Product $product;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::factory()->create();
        $this->restaurant->update(['vendor_id' => $this->restaurant->id]);

        $this->product = Product::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'price' => 10000,
            'is_available' => true,
        ]);
        $this->user = User::factory()->create(['phone' => '+99361234567']);
    }

    public function test_guest_cannot_create_order(): void
    {
        $response = $this->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson('/api/v1/orders', [
                'vendor_id' => $this->restaurant->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'product_name' => 'Test Product',
                        'quantity' => 1,
                        'unit_price' => 100,
                        'total_price' => 100,
                    ],
                ],
                'total' => 100,
                'payment_method' => 'card',
                'customer_name' => 'John Doe',
                'customer_phone' => '+99361234567',
                'address' => [
                    'address' => 'Main St 10',
                    'lat' => 39.0886,
                    'lon' => 63.5593,
                ],
            ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false, 'reason' => OrderRejectReason::UNAUTHORIZED->value]);
    }

    public function test_authenticated_user_can_create_order(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson('/api/v1/orders', [
                'vendor_id' => $this->restaurant->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'product_name' => $this->product->name,
                        'quantity' => 2,
                        'unit_price' => 10000,
                        'total_price' => 20000,
                    ],
                ],
                'total' => 20000,
                'payment_method' => 'card',
                'customer_name' => 'John Doe',
                'customer_phone' => '+99361234567',
                'address' => [
                    'address' => 'Main St 10',
                    'lat' => 39.0886,
                    'lon' => 63.5593,
                ],
            ]);

        $response->assertStatus(201);
        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('data.order_id'));
    }

    public function test_order_creation_is_idempotent(): void
    {
        $idempotencyKey = (string) Str::uuid();

        $payload = [
            'vendor_id' => $this->restaurant->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_name' => 'Pizza',
                    'quantity' => 2,
                    'unit_price' => 1000,
                    'total_price' => 2000,
                ]
            ],
            'total' => 2000,
            'payment_method' => 'card',
            'customer_name' => 'John Doe',
            'customer_phone' => '+99361234567',
            'address' => [
                'address' => 'Abashidze 1',
                'lat' => 39.0886,
                'lon' => 63.5593,
            ],
        ];

        $response1 = $this->actingAs($this->user)
            ->withHeaders([
                'X-Idempotency-Key' => $idempotencyKey,
                'X-Vendor-ID' => $this->restaurant->id,
            ])
            ->postJson('/api/v1/orders', $payload);

        $response1->assertStatus(201);
        $orderId = $response1->json('data.order_id');

        $response2 = $this->actingAs($this->user)
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
    }

    public function test_order_submission_without_key_creates_new_orders(): void
    {
        $payload = [
            'vendor_id' => $this->restaurant->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_name' => 'Pizza',
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'total_price' => 1000,
                ]
            ],
            'total' => 1000,
            'payment_method' => 'card',
            'customer_name' => 'John Doe',
            'customer_phone' => '+99361234567',
            'address' => [
                'address' => 'Main St 10',
                'lat' => 39.0886,
                'lon' => 63.5593,
            ],
        ];

        $response1 = $this->actingAs($this->user)
            ->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson('/api/v1/orders', $payload);

        $response1->assertStatus(201);

        $response2 = $this->actingAs($this->user)
            ->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson('/api/v1/orders', $payload);

        $response2->assertStatus(201);

        $this->assertNotEquals($response1->json('data.order_id'), $response2->json('data.order_id'));
    }

    public function test_order_respects_tenant_context(): void
    {
        $otherRestaurant = Restaurant::factory()->create();
        $otherRestaurant->update(['vendor_id' => $otherRestaurant->id]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson('/api/v1/orders', [
                'vendor_id' => $otherRestaurant->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                        'unit_price' => 100,
                        'total_price' => 100,
                    ],
                ],
                'total' => 100,
            ]);

        $response->assertStatus(400);
    }

    public function test_authenticated_user_can_list_own_orders(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_authenticated_user_can_view_order_details(): void
    {
        $orderPayload = [
            'vendor_id' => $this->restaurant->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_name' => 'Test',
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'total_price' => 1000,
                ]
            ],
            'total' => 1000,
            'payment_method' => 'card',
            'customer_name' => 'John Doe',
            'customer_phone' => '+99361234567',
            'address' => [
                'address' => 'Main St 10',
                'lat' => 39.0886,
                'lon' => 63.5593,
            ],
        ];

        $createResponse = $this->actingAs($this->user)
            ->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson('/api/v1/orders', $orderPayload);

        $createResponse->assertStatus(201);

        $orderId = $createResponse->json('data.order_id');

        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->getJson("/api/v1/orders/{$orderId}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['id' => $orderId],
            ]);
    }

    public function test_unauthorized_user_cannot_list_orders(): void
    {
        $response = $this->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->getJson('/api/v1/orders');

        $response->assertStatus(401);
    }

    public function test_order_fails_with_empty_cart(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson('/api/v1/orders', [
                'vendor_id' => $this->restaurant->id,
                'items' => [],
                'total' => 0,
                'payment_method' => 'card',
                'customer_name' => 'John Doe',
                'customer_phone' => '+99361234567',
                'address' => [
                    'address' => 'Main St 10',
                    'lat' => 39.0886,
                    'lon' => 63.5593,
                ],
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'reason' => OrderRejectReason::VALIDATION->value,
            ]);
    }

    public function test_order_fails_with_invalid_phone(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson('/api/v1/orders', [
                'vendor_id' => $this->restaurant->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'product_name' => $this->product->name,
                        'quantity' => 1,
                        'unit_price' => 10000,
                        'total_price' => 10000,
                    ],
                ],
                'total' => 10000,
                'payment_method' => 'card',
                'customer_name' => 'John Doe',
                'customer_phone' => '123', // invalid phone length
                'address' => [
                    'address' => 'Main St 10',
                    'lat' => 39.0886,
                    'lon' => 63.5593,
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'reason' => OrderRejectReason::INVALID_PHONE->value,
            ]);
    }

    public function test_order_fails_when_outside_delivery_zone(): void
    {
        // Mock GeoService to force an out_of_zone result since SQLite doesn't check PostGIS polygons
        $geoServiceMock = $this->mock(GeoService::class, function ($mock) {
            // Needed because calculateDeliveryFee is also called in the controller
            $mock->shouldReceive('calculateDeliveryFee')->andReturn(0);
            
            $mock->shouldReceive('checkDeliveryZone')->andReturn(
                new DeliveryZoneCheckResult(
                    status: 'out_of_zone',
                    allowed: false,
                    message: 'Адрес находится за пределами зоны доставки.',
                    debugContext: []
                )
            );
        });

        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson('/api/v1/orders', [
                'vendor_id' => $this->restaurant->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'product_name' => $this->product->name,
                        'quantity' => 1,
                        'unit_price' => 10000,
                        'total_price' => 10000,
                    ],
                ],
                'total' => 10000,
                'payment_method' => 'card',
                'customer_name' => 'John Doe',
                'customer_phone' => '+99361234567',
                'address' => [
                    'address' => 'Far Away',
                    'lat' => 40.0, // Outside the 39.0-39.1 polygon
                    'lon' => 64.0, // Outside
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'reason' => OrderRejectReason::OUTSIDE_DELIVERY_ZONE->value,
            ]);
    }
}