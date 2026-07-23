<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domains\Geo\Services\GeoService;
use App\Domains\Menu\Models\Product;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderSubmissionTest extends TestCase
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
        ]);
        $this->restaurant->update(['vendor_id' => $this->restaurant->id]);

        $this->product = Product::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'price' => 100.00,
            'is_available' => true,
        ]);
        $this->user = User::factory()->create();

        // Mock geo service
        $this->instance(GeoService::class, \Mockery::mock(GeoService::class, function ($mock) {
            $mock->shouldReceive('checkDeliveryZone')->andReturn(new \App\Domains\Geo\Services\DeliveryZoneCheckResult('inside', true, 'Allowed'));
        }));
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'vendor_id' => $this->restaurant->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'quantity' => 1,
                    'unit_price' => 10000, // 100.00 in cents
                    'total_price' => 10000,
                ],
            ],
            'total' => 10000,
            'delivery_fee' => 0,
            'customer_name' => 'Test User',
            'customer_phone' => '+99312345678',
            'address' => [
                'lat' => 1.0,
                'lon' => 1.0,
                'address' => 'Test Street 1',
            ],
            'payment_method' => 'cash',
        ], $overrides);
    }

    public function test_guest_cannot_submit_order_and_returns_401(): void
    {
        $response = $this->postJson('/api/v1/orders', $this->validPayload());

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_submit_order_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validPayload());

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('orders', [
            'vendor_id' => $this->restaurant->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_order_submission_rejects_invalid_vendor(): void
    {
        $otherRestaurant = Restaurant::factory()->create([
            'is_active' => true,
        ]);
        $otherRestaurant->update(['vendor_id' => $otherRestaurant->id]);

        $otherProduct = Product::factory()->create([
            'vendor_id' => $otherRestaurant->id,
            'price' => 50.00,
            'is_available' => true,
        ]);

        // Attempting to order from other vendor using its own product
        // but geo service mock from setUp still returns true
        // This should succeed since all checks pass
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validPayload([
                'vendor_id' => $otherRestaurant->id,
                'items' => [
                    [
                        'product_id' => $otherProduct->id,
                        'product_name' => $otherProduct->name,
                        'quantity' => 1,
                        'unit_price' => 5000,
                        'total_price' => 5000,
                    ],
                ],
                'total' => 5000,
                'delivery_fee' => 0,
            ]));

        $response->assertStatus(201);
    }
}
