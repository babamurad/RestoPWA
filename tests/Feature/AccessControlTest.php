<?php

namespace Tests\Feature;

use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    /**
     * test_guest_cannot_access_order_tracking
     * SEC-1: Unauthenticated user gets redirected to login.
     */
    public function test_guest_cannot_access_order_tracking(): void
    {
        $order = Order::factory()->create();

        $response = $this->get("/order/{$order->id}/track");

        $response->assertRedirect('/login');
    }

    /**
     * test_authenticated_user_cannot_access_another_users_order
     * SEC-2: User cannot access an order that doesn't belong to them.
     */
    public function test_authenticated_user_cannot_access_another_users_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->get("/order/{$order->id}/track");

        // The controller uses where('user_id', Auth::id())->findOrFail($orderId)
        // so it should return 404 if the order is not found for this user.
        $response->assertStatus(404);
    }

    /**
     * test_authenticated_user_can_access_own_order_tracking
     * SEC-2: Owner can access their own order tracking.
     */
    public function test_authenticated_user_can_access_own_order_tracking(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->get("/order/{$order->id}/track");

        $response->assertStatus(200);
        $response->assertViewIs('order.tracking');
    }

    /**
     * test_guest_cannot_access_vendor_panel
     * SEC-3: Unauthenticated user cannot access vendor dashboard.
     */
    public function test_guest_cannot_access_vendor_panel(): void
    {
        $response = $this->get("/vendor/orders");

        $response->assertRedirect('/login');
    }

    /**
     * test_vendor_cannot_see_other_vendors_orders
     * SEC-3: Vendor sees only their own orders.
     */
    public function test_vendor_cannot_see_other_vendors_orders(): void
    {
        $vendorUser = User::factory()->create();
        $restaurant = Restaurant::factory()->create(['owner_id' => $vendorUser->id]);
        
        $otherRestaurant = Restaurant::factory()->create();

        $myOrder = Order::factory()->create(['vendor_id' => $restaurant->id]);
        $otherOrder = Order::factory()->create(['vendor_id' => $otherRestaurant->id]);

        $response = $this->actingAs($vendorUser)
            ->withHeaders(['X-Vendor-ID' => $restaurant->id])
            ->get("/vendor/orders");

        $response->assertStatus(200);
        $response->assertViewHas('orders');
        
        $orders = $response->viewData('orders');
        $this->assertTrue($orders->contains('id', $myOrder->id));
        $this->assertFalse($orders->contains('id', $otherOrder->id));
    }
}
