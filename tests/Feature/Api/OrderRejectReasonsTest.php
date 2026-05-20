<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domains\Geo\Services\GeoService;
use App\Domains\Menu\Models\Product;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderRejectReasonsTest extends TestCase
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
            'delivery_fee' => 5.00,
            'min_order' => 10.00,
        ]);
        $this->restaurant->update(['vendor_id' => $this->restaurant->id]);

        $this->product = Product::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'price' => 1000,
            'is_available' => true,
        ]);
        $this->user = User::factory()->create();

        // Default mock for geo service
        $this->instance(\App\Domains\Geo\Services\GeoService::class, \Mockery::mock(\App\Domains\Geo\Services\GeoService::class, function ($mock) {
            $mock->shouldReceive('checkDeliveryZone')->andReturn(new \App\Domains\Geo\Services\DeliveryZoneCheckResult('inside', true, 'Allowed'));
            $mock->shouldReceive('geocodeWithFallback')->andReturn(null);
        }));
    }

    private function validOrderPayload(array $overrides = []): array
    {
        return array_merge([
            'vendor_id' => $this->restaurant->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'quantity' => 2,
                    'unit_price' => 1000,
                    'total_price' => 2000,
                ],
            ],
            'total' => 2500,
            'delivery_fee' => 500,
            'address' => [
                'lat' => 1.0,
                'lon' => 1.0,
                'address' => 'Test Street 1',
                'name' => 'Test User',
                'phone' => '+99312345678',
            ],
            'payment_method' => 'card',
        ], $overrides);
    }

    public function test_rejects_empty_cart_with_400_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'items' => [],
            ]));

        // Laravel validation catches 'min:1' on items array
        $response->assertStatus(400);
    }

    public function test_rejects_no_vendor_with_400_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'vendor_id' => '',
            ]));

        // Laravel validation catches 'required' on vendor_id
        $response->assertStatus(400);
    }

    public function test_rejects_vendor_not_found_with_422(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'vendor_id' => 'non-existent-uuid',
            ]));

        $response->assertStatus(422)
            ->assertJsonPath('reason', 'vendor_not_found');
    }

    public function test_rejects_invalid_vendor_inactive_with_422(): void
    {
        $inactiveRestaurant = Restaurant::factory()->create(['is_active' => false]);
        $inactiveRestaurant->update(['vendor_id' => $inactiveRestaurant->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'vendor_id' => $inactiveRestaurant->id,
            ]));

        $response->assertStatus(422)
            ->assertJsonPath('reason', 'invalid_vendor');
    }

    public function test_rejects_invalid_price_with_422(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'product_name' => $this->product->name,
                        'quantity' => 1,
                        'unit_price' => 0,
                        'total_price' => 0,
                    ],
                ],
                'total' => 500,
            ]));

        $response->assertStatus(422)
            ->assertJsonPath('reason', 'invalid_price');
    }

    public function test_rejects_invalid_total_mismatch_with_422(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'total' => 99999,
            ]));

        $response->assertStatus(422)
            ->assertJsonPath('reason', 'invalid_total');
    }

    public function test_rejects_missing_address_with_400_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'address' => [],
            ]));

        // Laravel validation catches required fields on address
        $response->assertStatus(400);
    }

    public function test_rejects_invalid_coordinates_with_400_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'address' => [
                    'lat' => 'not-numeric',
                    'lon' => 'not-numeric',
                    'address' => 'Test',
                    'name' => 'Test',
                    'phone' => '+99312345678',
                ],
            ]));

        // Laravel validation catches required numeric lat/lon
        $response->assertStatus(400);
    }

    public function test_rejects_missing_name_with_400_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'address' => [
                    'lat' => 1.0,
                    'lon' => 1.0,
                    'address' => 'Test',
                    'name' => 123,
                    'phone' => '+99312345678',
                ],
            ]));

        // Laravel validation catches required name
        $response->assertStatus(400);
    }

    public function test_rejects_invalid_phone_with_422(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'address' => [
                    'lat' => 1.0,
                    'lon' => 1.0,
                    'address' => 'Test',
                    'name' => 'Test User',
                    'phone' => 'not-a-phone',
                ],
            ]));

        $response->assertStatus(422)
            ->assertJsonPath('reason', 'invalid_phone');
    }

    public function test_rejects_invalid_payment_method_with_422(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'payment_method' => 'bitcoin',
            ]));

        $response->assertStatus(422)
            ->assertJsonPath('reason', 'invalid_payment_method');
    }

    public function test_rejects_unauthorized_with_401(): void
    {
        $response = $this->postJson('/api/v1/orders', $this->validOrderPayload());

        $response->assertStatus(401);
    }

    public function test_rejects_below_min_order_with_422(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'product_name' => $this->product->name,
                        'quantity' => 1,
                        'unit_price' => 100,
                        'total_price' => 100,
                    ],
                ],
                'total' => 600,
                'delivery_fee' => 500,
            ]));

        $response->assertStatus(422)
            ->assertJsonPath('reason', 'below_min_order');
    }

    public function test_valid_order_succeeds_with_201(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload());

        if ($response->status() !== 201) {
            dump('Response:', $response->json());
        }

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'order_id',
                    'status',
                    'redirect_url',
                ],
            ]);
    }

    public function test_validation_error_returns_user_friendly_message(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'items' => [],
            ]));

        $response->assertStatus(400);
        $this->assertNotEmpty($response->json('message'));
    }
}
