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
use Tests\TestCase;

class OrderModifierPriceValidationTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;
    private Product $product;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::factory()->create(['is_active' => true]);
        $this->restaurant->update(['vendor_id' => $this->restaurant->id]);

        $this->product = Product::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'price' => 100.00, // 100.00 currency units → 10000 cents
            'modifiers' => [
                [
                    'name' => 'Размер',
                    'type' => 'radio',
                    'options' => [
                        ['name' => 'Маленькая', 'price' => 0, 'is_default' => true],
                        ['name' => 'Большая', 'price' => 50, 'is_default' => false], // +50 currency units
                    ],
                ],
                [
                    'name' => 'Топпинг',
                    'type' => 'checkbox',
                    'options' => [
                        ['name' => 'Сыр', 'price' => 30, 'is_default' => false],   // +30 currency units
                        ['name' => 'Грибы', 'price' => 25, 'is_default' => false], // +25 currency units
                    ],
                ],
            ],
            'is_available' => true,
        ]);

        $this->user = User::factory()->create(['phone' => '+99361234567']);

        $this->mock(GeoService::class, function ($mock) {
            $mock->shouldReceive('checkDeliveryZone')
                ->andReturn(new DeliveryZoneCheckResult('inside', true, 'Allowed'));
            $mock->shouldReceive('calculateDeliveryFee')->andReturn(0);
        });
    }

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'vendor_id' => $this->restaurant->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'quantity' => 1,
                    'unit_price' => 10000,
                    'total_price' => 10000,
                    'modifiers' => [],
                ],
            ],
            'total' => 10000,
            'delivery_fee' => 0,
            'customer_name' => 'Тест',
            'customer_phone' => '+99361234567',
            'address' => [
                'lat' => 39.0886,
                'lon' => 63.5593,
                'address' => 'Тестовая 1',
            ],
            'payment_method' => 'cash',
        ], $overrides);
    }

    public function test_order_total_is_recalculated_from_db_not_client(): void
    {
        // Client tries to pay 100 currency units for a 100-unit product — but claims 999999 total
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->basePayload([
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'product_name' => $this->product->name,
                        'quantity' => 1,
                        'unit_price' => 10000, // honest base price
                        'total_price' => 10000,
                        'modifiers' => [],
                    ],
                ],
                // Forged total — 999999 instead of expected 10000
                'total' => 999999,
            ]));

        $response->assertStatus(201);
        $orderId = $response->json('data.order_id');

        $order = Order::find($orderId);
        // MoneyCast converts cents→currency units: 10000 cents → 100.00
        // Server must have recalculated total from item total_prices, not the forged value
        $this->assertEquals(100.00, $order->total);
    }

    public function test_forged_unit_price_is_overwritten_with_db_price(): void
    {
        // Client sends unit_price = 1 cent (trying to get a free product)
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->basePayload([
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'product_name' => $this->product->name,
                        'quantity' => 2,
                        'unit_price' => 1,       // forged: 0.01 currency units
                        'total_price' => 2,       // forged: 0.02 total
                        'modifiers' => [],
                    ],
                ],
                'total' => 2,
            ]));

        $response->assertStatus(201);
        $orderId = $response->json('data.order_id');

        $order = Order::find($orderId);
        $items = $order->items;

        // Server recalculated: base 10000 cents × 2 = 20000
        $this->assertEquals(10000, $items[0]['unit_price']);
        $this->assertEquals(20000, $items[0]['total_price']);
        // MoneyCast: 20000 cents → 200.00
        $this->assertEquals(200.00, $order->total);
    }

    public function test_forged_modifier_price_is_replaced_with_db_price(): void
    {
        // Client sends modifier objects with forged prices.
        // DB has "Большая" at 50 currency units = 5000 cents.
        // Client claims it costs 9999 currency units.
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->basePayload([
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'product_name' => $this->product->name,
                        'quantity' => 1,
                        'unit_price' => 10000 + 999900, // 100 base + 9999 forged modifier
                        'total_price' => 10000 + 999900,
                        'modifiers' => [
                            ['id' => 'size_large', 'name' => 'Большая', 'price' => 999900], // forged price
                        ],
                    ],
                ],
                'total' => 10000 + 999900,
            ]));

        $response->assertStatus(201);
        $orderId = $response->json('data.order_id');

        $order = Order::find($orderId);
        $items = $order->items;

        // Server recalculated: 10000 base + 5000 modifier ("Большая" = 50 currency units)
        $this->assertEquals(15000, $items[0]['unit_price']);
        $this->assertEquals(15000, $items[0]['total_price']);
        $this->assertEquals(150.00, $order->total);
    }

    public function test_multiple_forged_modifiers_recalculated_from_db(): void
    {
        // Client tries to inflate prices of two modifiers
        // DB: "Большая" = 50, "Сыр" = 30 → total modifier = 80 currency units = 8000 cents
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->basePayload([
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'product_name' => $this->product->name,
                        'quantity' => 1,
                        'unit_price' => 10000 + 50000 + 30000, // base + forged modifiers
                        'total_price' => 10000 + 50000 + 30000,
                        'modifiers' => [
                            ['name' => 'Большая', 'price' => 50000],  // forged: 500 vs real 50
                            ['name' => 'Сыр', 'price' => 30000],     // forged: 300 vs real 30
                        ],
                    ],
                ],
                'total' => 10000 + 50000 + 30000,
            ]));

        $response->assertStatus(201);
        $orderId = $response->json('data.order_id');

        $order = Order::find($orderId);
        $items = $order->items;

        // 10000 base + 5000 (Большая) + 3000 (Сыр) = 18000
        $this->assertEquals(18000, $items[0]['unit_price']);
        $this->assertEquals(18000, $items[0]['total_price']);
        $this->assertEquals(180.00, $order->total);
    }

    public function test_zero_price_modifier_in_client_gets_db_price(): void
    {
        // Client sends a modifier with price 0 — but DB has it at 30 currency units
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->basePayload([
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'product_name' => $this->product->name,
                        'quantity' => 1,
                        'unit_price' => 10000, // only base price, no modifier cost
                        'total_price' => 10000,
                        'modifiers' => [
                            ['name' => 'Сыр', 'price' => 0], // forged: free, real = 30
                        ],
                    ],
                ],
                'total' => 10000,
            ]));

        $response->assertStatus(201);
        $orderId = $response->json('data.order_id');

        $order = Order::find($orderId);
        $items = $order->items;

        // 10000 base + 3000 (Сыр from DB) = 13000
        $this->assertEquals(13000, $items[0]['unit_price']);
        $this->assertEquals(13000, $items[0]['total_price']);
        $this->assertEquals(130.00, $order->total);
    }

    public function test_honest_order_with_correct_modifier_prices_succeeds(): void
    {
        // Honest client sends correct DB prices
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->basePayload([
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'product_name' => $this->product->name,
                        'quantity' => 1,
                        'unit_price' => 15000, // 100 base + 50 modifier = correct
                        'total_price' => 15000,
                        'modifiers' => [
                            ['name' => 'Большая', 'price' => 50], // correct DB price
                        ],
                    ],
                ],
                'total' => 15000,
            ]));

        $response->assertStatus(201);
        $orderId = $response->json('data.order_id');

        $order = Order::find($orderId);
        $items = $order->items;

        $this->assertEquals(15000, $items[0]['unit_price']);
        $this->assertEquals(15000, $items[0]['total_price']);
        $this->assertEquals(150.00, $order->total);
    }

    public function test_quantity_multiplies_recalculated_price(): void
    {
        // Client forges price and sends quantity 3
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->basePayload([
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'product_name' => $this->product->name,
                        'quantity' => 3,
                        'unit_price' => 1,       // forged: 0.01
                        'total_price' => 3,       // forged: 0.03
                        'modifiers' => [
                            ['name' => 'Большая', 'price' => 0], // forged: free
                        ],
                    ],
                ],
                'total' => 3,
            ]));

        $response->assertStatus(201);
        $orderId = $response->json('data.order_id');

        $order = Order::find($orderId);
        $items = $order->items;

        // (10000 base + 5000 modifier) × 3 = 45000
        $this->assertEquals(15000, $items[0]['unit_price']);
        $this->assertEquals(45000, $items[0]['total_price']);
        $this->assertEquals(450.00, $order->total);
    }

    public function test_unknown_modifier_name_is_ignored(): void
    {
        // Client sends a modifier that doesn't exist in DB — should be ignored
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->basePayload([
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'product_name' => $this->product->name,
                        'quantity' => 1,
                        'unit_price' => 10000 + 999900,
                        'total_price' => 10000 + 999900,
                        'modifiers' => [
                            ['name' => 'Несуществующий', 'price' => 999900],
                        ],
                    ],
                ],
                'total' => 10000 + 999900,
            ]));

        $response->assertStatus(201);
        $orderId = $response->json('data.order_id');

        $order = Order::find($orderId);
        $items = $order->items;

        // Only base price — unknown modifier ignored
        $this->assertEquals(10000, $items[0]['unit_price']);
        $this->assertEquals(10000, $items[0]['total_price']);
        $this->assertEquals(100.00, $order->total);
    }

    public function test_forged_delivery_fee_is_recalculated_from_geo_service(): void
    {
        // Client tries to pay almost nothing for delivery by forging delivery_fee + a matching total.
        // Server must ignore both and always (re)compute delivery_fee via GeoService, same policy
        // as item/modifier prices above.
        $this->mock(GeoService::class, function ($mock) {
            $mock->shouldReceive('checkDeliveryZone')
                ->andReturn(new DeliveryZoneCheckResult('inside', true, 'Allowed'));
            $mock->shouldReceive('calculateDeliveryFee')->andReturn(25.00); // 25.00 currency units → 2500 cents
        });

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->basePayload([
                'delivery_fee' => 1,  // forged: near-zero delivery fee
                'total' => 10001,     // forged to match the forged delivery_fee
            ]));

        $response->assertStatus(201);
        $orderId = $response->json('data.order_id');

        $order = Order::find($orderId);

        // MoneyCast: 2500 recalculated cents → 25.00 currency units — not the client's forged value.
        $this->assertEquals(25.00, $order->delivery_fee);
        // MoneyCast: (10000 item cents + 2500 delivery cents) → 125.00 currency units
        $this->assertEquals(125.00, $order->total);
    }
}
