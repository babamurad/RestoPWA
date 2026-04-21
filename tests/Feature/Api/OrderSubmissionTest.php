<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

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

        $this->restaurant = Restaurant::factory()->create();
        $this->restaurant->update(['vendor_id' => $this->restaurant->id]);

        $this->product = Product::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'price' => 10000, // 100.00
            'is_available' => true,
        ]);
        $this->user = User::factory()->create();
    }

    public function test_guest_cannot_submit_order_and_returns_401(): void
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
            ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_submit_order_successfully(): void
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
                        'unit_price' => 100,
                        'total_price' => 200,
                    ],
                ],
                'total' => 200,
                'address' => [
                    'street' => 'Main St',
                    'house' => '10',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('orders', [
            'vendor_id' => $this->restaurant->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_order_submission_respects_tenant_context(): void
    {
        $otherRestaurant = Restaurant::factory()->create();
        $otherRestaurant->update(['vendor_id' => $otherRestaurant->id]);

        // Attempting to order from other-vendor while header says test-vendor
        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson('/api/v1/orders', [
                'vendor_id' => $otherRestaurant->id, // Conflict!
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

        // Should fail with 404 because the Global Scope prevents finding
        // a restaurant that doesn't belong to the current tenant context.
        $response->assertStatus(404);
    }
}
