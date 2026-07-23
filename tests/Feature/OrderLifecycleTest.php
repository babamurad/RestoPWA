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
            'price' => 100.00, // 10000 cents
            'is_available' => true,
        ]);

        $this->user = User::factory()->create(['phone' => '+99361234567']);
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
            ->assertJsonPath('data.total', 200); // 200 dollars

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
                        'unit_price' => 10000,
                        'total_price' => 20000,
                    ],
                ],
                'total' => 20000,
                'payment_method' => 'cash',
                'address' => [
                    'address' => 'Test Street 10',
                    'lat' => 39.0886,
                    'lon' => 63.5593,
                    'name' => 'John Doe',
                    'phone' => '+99361234567',
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
        $this->product->update(['price' => 150.00]); // 15000 cents

        // Client thinks it is still 100.00 (10000 cents)
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
        $this->assertEquals(15000, $response->json('data.items.0.price')); // Item price returned in cents (15000)
        $this->assertEquals(150.0, $response->json('data.total')); // Cart total returned in dollars (150.0)
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
        // The sync should mark it as unavailable and put in unavailable_items
        $this->assertCount(0, $response->json('data.items'));
        $this->assertCount(1, $response->json('data.unavailable_items'));
        $this->assertEquals($this->product->id, $response->json('data.unavailable_items.0.product_id'));
    }

    /**
     * B2: Test tenant isolation (Negative scenario).
     */
    public function test_tenant_isolation_enforcement(): void
    {
        $otherRestaurant = Restaurant::factory()->create();
        $otherRestaurant->update(['vendor_id' => $otherRestaurant->id]);

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
                'total' => 10000,
            ]);

        // Should return 400 because of tenant mismatch check in SetTenantContext middleware
        $response->assertStatus(400);
    }
}
