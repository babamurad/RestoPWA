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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
            'price' => 10.00,
            'is_available' => true,
        ]);
        $this->user = User::factory()->create();

        // Default mock for geo service
        $this->instance(GeoService::class, \Mockery::mock(GeoService::class, function ($mock) {
            $mock->shouldReceive('checkDeliveryZone')->andReturn(new DeliveryZoneCheckResult('inside', true, 'Allowed'));
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

    public function test_rejects_restaurant_closed_with_422(): void
    {
        $closedRestaurant = Restaurant::factory()->create(['is_active' => true, 'is_paused' => true]);
        $closedRestaurant->update(['vendor_id' => $closedRestaurant->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'vendor_id' => $closedRestaurant->id,
            ]));

        $response->assertStatus(422)
            ->assertJsonPath('reason', 'restaurant_closed');
    }

    public function test_forged_price_is_overwritten_by_server(): void
    {
        // Client sends unit_price=0, total_price=0 — server recalculates from DB
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

        // Server overwrites with DB price — order succeeds
        $response->assertStatus(201)->assertJson(['success' => true]);
    }

    public function test_forged_total_is_recalculated_by_server(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'total' => 99999,
            ]));

        // Server recalculates total from items — order succeeds
        $response->assertStatus(201)->assertJson(['success' => true]);
    }

    public function test_rejects_missing_address_with_422(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'address' => [],
            ]));

        // Preprocessing fills default lat/lon, but no text field → precondition catches it
        $response->assertStatus(422)
            ->assertJsonPath('reason', 'missing_address');
    }

    public function test_rejects_invalid_coordinates_with_400_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'address' => [
                    'lat' => 'not-numeric',
                    'lon' => 'not-numeric',
                    'address' => 'Test',
                ],
            ]));

        // Laravel validation catches required numeric lat/lon
        $response->assertStatus(400);
    }

    public function test_rejects_missing_name_with_400_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'customer_name' => 123,
            ]));

        // Laravel validation catches required name
        $response->assertStatus(400);
    }

    public function test_rejects_invalid_phone_with_422(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'customer_phone' => 'not-a-phone',
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
        // Set min_order above what the server-recalculated total will be
        // Product price = 10.00, quantity 1 → 1000 cents; delivery_fee = 500 cents; total = 1500 cents
        // min_order = 20.00 = 2000 cents → 1500 < 2000 → rejected
        $this->restaurant->update(['min_order' => 20.00]);

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

    public function test_product_name_comes_from_db_not_from_client(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'product_name' => 'Чёрная икра 1 кг',
                        'quantity' => 2,
                        'modifiers' => [['id' => 'made-up', 'name' => 'Бесплатный стейк', 'price' => 0]],
                    ],
                ],
            ]));

        $response->assertStatus(201);

        $item = Order::findOrFail($response->json('data.order_id'))->items[0];
        $this->assertSame($this->product->name, $item['name']);
        $this->assertSame([], $item['modifiers']);
    }

    public function test_rejects_unavailable_product_with_422(): void
    {
        $this->product->update(['is_available' => false]);

        $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload())
            ->assertStatus(422)
            ->assertJsonPath('reason', 'item_unavailable');
    }

    public function test_rejects_unknown_product_with_422(): void
    {
        $payload = $this->validOrderPayload();
        $payload['items'][0]['product_id'] = (string) Str::uuid();

        $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $payload)
            ->assertStatus(422)
            ->assertJsonPath('reason', 'item_not_found');
    }

    public function test_rejects_product_of_another_restaurant_with_422(): void
    {
        $other = Restaurant::factory()->create(['is_active' => true]);
        $foreign = Product::factory()->create(['vendor_id' => $other->id, 'price' => 10.00, 'is_available' => true]);

        $payload = $this->validOrderPayload();
        $payload['items'][0]['product_id'] = $foreign->id;

        $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $payload)
            ->assertStatus(422)
            ->assertJsonPath('reason', 'item_not_found');
    }

    public function test_rejects_delivery_when_address_cannot_be_geocoded(): void
    {
        // No coordinates from the client and the geocoder (mocked in setUp) finds nothing:
        // the order must not be created at a default point
        $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->validOrderPayload([
                'address' => ['address' => 'Несуществующая улица 999'],
            ]))
            ->assertStatus(422)
            ->assertJsonPath('reason', 'invalid_coordinates');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_unauthenticated_attempt_does_not_log_cookies_or_headers(): void
    {
        Log::spy();

        $this->withHeader('Authorization', 'Bearer secret-token-value')
            ->postJson('/api/v1/orders', $this->validOrderPayload())
            ->assertStatus(401);

        Log::shouldNotHaveReceived('error');
        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message, $context = []) => ! str_contains(json_encode($context), 'secret-token-value')
                && ! array_key_exists('cookies', $context)
                && ! array_key_exists('headers', $context));
    }
}
