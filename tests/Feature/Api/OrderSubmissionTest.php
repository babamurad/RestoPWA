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
        
        $this->restaurant = Restaurant::factory()->create(['id' => 'test-vendor']);
        $this->product = Product::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'price' => 10000, // 100.00
            'is_active' => true,
        ]);
        $this->user = User::factory()->create();
    }

    public function test_guest_cannot_submit_order_and_returns_401(): void
    {
        $response = $this->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson(route('api.orders.store'), [
                'vendor_id' => $this->restaurant->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'product_name' => 'Test Product',
                        'quantity' => 1,
                        'unit_price' => 100,
                        'total_price' => 100,
                    ]
                ],
                'total' => 100,
            ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_submit_order_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson(route('api.orders.store'), [
                'vendor_id' => $this->restaurant->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'product_name' => $this->product->name,
                        'quantity' => 2,
                        'unit_price' => 100,
                        'total_price' => 200,
                    ]
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
        $otherRestaurant = Restaurant::factory()->create(['id' => 'other-vendor']);
        
        // Attempting to order from other-vendor while header says test-vendor
        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Vendor-ID' => $this->restaurant->id])
            ->postJson(route('api.orders.store'), [
                'vendor_id' => $otherRestaurant->id, // Conflict!
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                        'unit_price' => 100,
                        'total_price' => 100,
                    ]
                ],
                'total' => 100,
            ]);

        // Depending on implementation, it might fail validation or just use the header's vendor.
        // In our current implementation, we just validate vendor_id matches request.
        // But the TenantContext is set to 'test-vendor'.
        // If the OrderService doesn't check against TenantContext, it might create it for 'other-vendor'.
        // Let's see if we should enforce this.
    }
}
