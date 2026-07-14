<?php

namespace Tests\Feature\Api;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Order\Models\Order;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

class CourierApiTest extends TestCase
{
    use RefreshDatabase;

    #[Group('courier')]
    public function test_courier_can_get_available_orders(): void
    {
        $user = User::factory()->create(['role' => UserRole::COURIER]);
        $courier = Courier::create(['user_id' => $user->id]);

        $order = Order::factory()->create([
            'status' => Order::STATUS_READY_FOR_PICKUP,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/courier/orders');
        if ($response->status() !== 200) {
            $response->dump();
        }

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.available');
        $response->assertJsonPath('data.available.0.id', $order->id);
    }

    #[Group('courier')]
    public function test_courier_can_accept_order(): void
    {
        $user = User::factory()->create(['role' => UserRole::COURIER]);
        $courier = Courier::create(['user_id' => $user->id]);

        $order = Order::factory()->create([
            'status' => Order::STATUS_READY_FOR_PICKUP,
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/courier/orders/{$order->id}/accept");
        if ($response->status() !== 200) {
            $response->dump();
        }

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'courier_id' => $courier->id,
            'status' => Order::STATUS_DELIVERING,
        ]);
    }
}
