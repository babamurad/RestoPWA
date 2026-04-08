<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domains\Menu\Models\Product;
use App\Domains\Vendor\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartSyncTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::factory()->create([
            'min_order' => 500,
        ]);
        $this->restaurant->update(['vendor_id' => $this->restaurant->id]);

        $this->product = Product::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'price' => 20000, // 200.00 in MoneyCast units (store as integer 20000)
            'is_available' => true,
        ]);
    }

    public function test_cart_sync_calculates_totals_correctly(): void
    {
        $response = $this->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson(route('api.cart.sync'), [
                'vendor_id' => $this->restaurant->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                        'modifiers' => [],
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'subtotal' => 400,
                    'total' => 400,
                    'is_min_order_met' => false,
                ],
            ]);
    }

    public function test_cart_sync_detects_min_order_met(): void
    {
        $response = $this->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson(route('api.cart.sync'), [
                'vendor_id' => $this->restaurant->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 3, // 3 * 200 = 600
                        'modifiers' => [],
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_min_order_met' => true,
                ],
            ]);
    }

    public function test_cart_sync_fails_without_vendor_header(): void
    {
        $response = $this->postJson(route('api.cart.sync'), [
            'vendor_id' => $this->restaurant->id,
            'items' => [],
        ]);

        $response->assertStatus(400);
    }
}
