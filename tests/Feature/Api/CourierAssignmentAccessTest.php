<?php

namespace Tests\Feature\Api;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Regression tests for courier assignment access control:
 * a restaurateur must not manage other restaurants' orders,
 * and an order must not end up with two couriers.
 */
class CourierAssignmentAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourier(?string $vendorId = null): Courier
    {
        $user = User::factory()->create(['role' => UserRole::COURIER]);

        return Courier::create(['user_id' => $user->id, 'vendor_id' => $vendorId]);
    }

    #[Group('courier')]
    public function test_restaurateur_can_assign_courier_to_own_restaurant_order(): void
    {
        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);
        $courier = $this->makeCourier();

        $order = Order::factory()->create([
            'vendor_id' => $restaurant->id,
            'status' => Order::STATUS_READY_FOR_PICKUP,
        ]);

        $this->actingAs($owner)
            ->postJson("/api/v1/orders/{$order->id}/assign", ['courier_id' => $courier->id])
            ->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'courier_id' => $courier->id,
            'status' => Order::STATUS_DELIVERING,
        ]);
    }

    #[Group('courier')]
    public function test_restaurateur_cannot_assign_courier_to_foreign_restaurant_order(): void
    {
        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        Restaurant::factory()->create(['owner_id' => $owner->id]);
        $foreignRestaurant = Restaurant::factory()->create();
        $courier = $this->makeCourier();

        $order = Order::factory()->create([
            'vendor_id' => $foreignRestaurant->id,
            'status' => Order::STATUS_READY_FOR_PICKUP,
        ]);

        $this->actingAs($owner)
            ->postJson("/api/v1/orders/{$order->id}/assign", ['courier_id' => $courier->id])
            ->assertStatus(403);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'courier_id' => null,
            'status' => Order::STATUS_READY_FOR_PICKUP,
        ]);
    }

    #[Group('courier')]
    public function test_client_cannot_assign_courier(): void
    {
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $courier = $this->makeCourier();
        $order = Order::factory()->create(['status' => Order::STATUS_READY_FOR_PICKUP]);

        $this->actingAs($client)
            ->postJson("/api/v1/orders/{$order->id}/assign", ['courier_id' => $courier->id])
            ->assertStatus(403);
    }

    #[Group('courier')]
    public function test_courier_of_another_restaurant_cannot_be_assigned(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $restaurant = Restaurant::factory()->create();
        $otherRestaurant = Restaurant::factory()->create();
        $courier = $this->makeCourier($otherRestaurant->id);

        $order = Order::factory()->create([
            'vendor_id' => $restaurant->id,
            'status' => Order::STATUS_READY_FOR_PICKUP,
        ]);

        $this->actingAs($admin)
            ->postJson("/api/v1/orders/{$order->id}/assign", ['courier_id' => $courier->id])
            ->assertStatus(422);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'courier_id' => null]);
    }

    #[Group('courier')]
    public function test_second_courier_cannot_accept_already_accepted_order(): void
    {
        $first = $this->makeCourier();
        $second = $this->makeCourier();

        $order = Order::factory()->create(['status' => Order::STATUS_READY_FOR_PICKUP]);

        $this->actingAs($first->user)
            ->postJson("/api/v1/courier/orders/{$order->id}/accept")
            ->assertStatus(200);

        $this->actingAs($second->user)
            ->postJson("/api/v1/courier/orders/{$order->id}/accept")
            ->assertStatus(409);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'courier_id' => $first->id,
        ]);
    }
}
