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

    #[Group('courier')]
    public function test_courier_can_update_profile_status(): void
    {
        $user = User::factory()->create(['role' => UserRole::COURIER]);
        $courier = Courier::create(['user_id' => $user->id, 'status' => 'offline']);

        $response = $this->actingAs($user)->postJson('/api/v1/courier/status', [
            'status' => 'available'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('available', $courier->fresh()->status);
    }

    #[Group('courier')]
    public function test_admin_can_assign_courier(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $user = User::factory()->create(['role' => UserRole::COURIER]);
        $courier = Courier::create(['user_id' => $user->id]);

        $order = Order::factory()->create([
            'status' => Order::STATUS_READY_FOR_PICKUP,
        ]);

        $response = $this->actingAs($admin)->postJson("/api/v1/orders/{$order->id}/assign", [
            'courier_id' => $courier->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'courier_id' => $courier->id,
            'status' => Order::STATUS_DELIVERING,
        ]);
    }

    #[Group('courier')]
    public function test_courier_earns_money_on_delivery(): void
    {
        $restaurant = \App\Domains\Vendor\Models\Restaurant::factory()->create([
            'courier_fixed_fee' => 10,
            'courier_percent_fee' => 5,
        ]);

        $user = User::factory()->create(['role' => UserRole::COURIER]);
        $courier = Courier::create(['user_id' => $user->id]);

        $order = Order::factory()->create([
            'vendor_id' => $restaurant->id,
            'courier_id' => $courier->id,
            'status' => Order::STATUS_DELIVERING,
            'total' => 1000,
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/courier/orders/{$order->id}/status", [
            'status' => Order::STATUS_DELIVERED,
        ]);

        $response->assertStatus(200);

        // Fixed = 10, Percent = 5% of 1000 = 50. Total = 60.
        $this->assertDatabaseHas('courier_earnings', [
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'amount' => 60.00,
        ]);

        $earningsResponse = $this->actingAs($user)->getJson("/api/v1/courier/earnings");
        $earningsResponse->assertStatus(200);
        $earningsResponse->assertJsonPath('data.0.amount', 60);
    }
}
