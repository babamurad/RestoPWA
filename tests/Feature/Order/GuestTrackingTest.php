<?php

namespace Tests\Feature\Order;

use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class GuestTrackingTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;
    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->restaurant = Restaurant::factory()->create();
        $this->order = Order::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'user_id' => null, // Guest order
        ]);
    }

    public function test_guest_cannot_access_order_tracking_without_signature(): void
    {
        // Try accessing the auth route
        $response = $this->get(route('order.track', ['orderId' => $this->order->id]));
        $response->assertRedirect(route('login'));

        // Try accessing the guest route without signature
        $response = $this->get('/order/' . $this->order->id . '/track/guest');
        $response->assertStatus(403);
    }

    public function test_guest_can_access_order_tracking_with_valid_signed_url(): void
    {
        $url = URL::temporarySignedRoute(
            'order.track.guest',
            now()->addHours(24),
            ['orderId' => $this->order->id]
        );

        $response = $this->get($url);
        
        $response->assertStatus(200);
        $response->assertSee($this->order->id);
        $response->assertSee('noindex');
    }

    public function test_guest_cannot_access_order_tracking_with_expired_signature(): void
    {
        $url = URL::temporarySignedRoute(
            'order.track.guest',
            now()->subHours(1), // Already expired
            ['orderId' => $this->order->id]
        );

        $response = $this->get($url);
        $response->assertStatus(403);
    }

    public function test_api_guest_tracking_with_signature(): void
    {
        $url = URL::temporarySignedRoute(
            'api.order.track.guest',
            now()->addHours(24),
            ['orderId' => $this->order->id]
        );

        $response = $this->getJson($url);
        
        if ($response->status() !== 200) {
            $response->dump();
        }

        $response->assertStatus(200);
        $response->assertJsonPath('order_id', $this->order->id);
    }
}
