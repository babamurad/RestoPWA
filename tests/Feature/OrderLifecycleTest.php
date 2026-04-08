<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Menu\Models\Product;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    private Product $product;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::factory()->create([
            'slug' => 'test-restaurant',
            'is_active' => true,
        ]);
        $this->restaurant->update(['vendor_id' => $this->restaurant->id]);

        $this->product = Product::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'price' => 10000, // 100.00
            'is_available' => true,
        ]);

        $this->user = User::factory()->create();
    }

    /**
     * B1: Test the full happy path flow.
     */
    public function test_full_happy_path_flow(): void
    {
        // 1. Get Menu
        $response = $this->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->getJson("/api/v1/menu/{$this->restaurant->slug}");
        $response->assertStatus(200);

        // 2. Sync Cart
        $response = $this->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson('/api/v1/cart/sync', [
                'vendor_id' => $this->restaurant->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                    ],
                ],
            ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.total', 200.0);

        // 3. Create Order (requires authentication)
        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson('/api/v1/orders', [
                'vendor_id' => $this->restaurant->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'product_name' => $this->product->name,
                        'quantity' => 2,
                        'unit_price' => 100.0,
                        'total_price' => 200.0,
                    ],
                ],
                'total' => 200.0,
                'address' => [
                    'address' => 'Test Street 10',
                    'lat' => 55.75,
                    'lon' => 37.61,
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'vendor_id' => $this->restaurant->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    /**
     * B2: Test price shift reconciliation.
     */
    public function test_cart_sync_reconciles_price_shift(): void
    {
        // Change product price on server
        $this->product->update(['price' => 15000]); // 150.00

        // Client thinks it is still 100.00
        $response = $this->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson('/api/v1/cart/sync', [
                'vendor_id' => $this->restaurant->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                        'price' => 10000,
                    ],
                ],
            ]);

        $response->assertStatus(200);
        $this->assertEquals(150.0, $response->json('data.items.0.price'));
        $this->assertEquals(150.0, $response->json('data.total'));
    }

    /**
     * B2: Test unavailable product rejection.
     */
    public function test_inactive_product_handling(): void
    {
        $this->product->update(['is_available' => false]);

        $response = $this->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson('/api/v1/cart/sync', [
                'vendor_id' => $this->restaurant->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertStatus(200);
        // The sync should mark it as unavailable or remove it
        $this->assertFalse($response->json('data.items.0.is_available'));
    }

    /**
     * B2: Test tenant isolation (Negative scenario).
     */
    public function test_tenant_isolation_enforcement(): void
    {
        $otherRestaurant = Restaurant::factory()->create(['id' => 'other-vendor']);

        // Attempting to order our product using other-vendor context
        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Vendor-ID' => $otherRestaurant->id])
            ->postJson('/api/v1/orders', [
                'vendor_id' => $this->restaurant->id, // Mismatch with Header
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                    ],
                ],
                'total' => 100.0,
            ]);

        // Should return 400 or 403 because current vendor is set to 'other-vendor'
        // and we are trying to create an order for 'test-vendor'.
        // Or the Global Scope will prevent finding either.
        $response->assertStatus(400);
    }
}
