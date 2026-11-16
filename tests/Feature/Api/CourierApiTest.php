<?php

namespace Tests\Feature\Api;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\CourierEarning;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class CourierApiTest extends TestCase
{
    use RefreshDatabase;

    #[Group('courier')]
    public function test_courier_cannot_end_the_shift_with_an_order_on_the_way(): void
    {
        $user = User::factory()->create(['role' => UserRole::COURIER]);
        $courier = Courier::create(['user_id' => $user->id, 'status' => Courier::STATUS_AVAILABLE]);
        $order = Order::factory()->create(['status' => Order::STATUS_DELIVERING, 'courier_id' => $courier->id]);

        $this->actingAs($user)->postJson('/api/v1/courier/status', ['status' => 'offline'])
            ->assertStatus(422)
            ->assertJsonPath('message', __('api.courier.finish_delivery_first'));
        $this->assertSame(Courier::STATUS_AVAILABLE, $courier->fresh()->status);

        $order->update(['status' => Order::STATUS_DELIVERED]);

        $this->actingAs($user)->postJson('/api/v1/courier/status', ['status' => 'offline'])->assertOk();
        $this->assertSame(Courier::STATUS_OFFLINE, $courier->fresh()->status);
    }

    #[Group('courier')]
    public function test_orders_response_carries_the_shift_status_and_the_restaurant_name(): void
    {
        $user = User::factory()->create(['role' => UserRole::COURIER]);
        $courier = Courier::create(['user_id' => $user->id, 'status' => Courier::STATUS_AVAILABLE]);
        $order = Order::factory()->create(['status' => Order::STATUS_READY_FOR_PICKUP]);

        $this->actingAs($user)->getJson('/api/v1/courier/orders')
            ->assertOk()
            ->assertJsonPath('data.courier.id', $courier->id)
            ->assertJsonPath('data.courier.status', Courier::STATUS_AVAILABLE)
            ->assertJsonPath('data.available.0.restaurant.name', $order->restaurant->name);
    }

    #[Group('courier')]
    public function test_earnings_have_period_totals_and_no_customer_data(): void
    {
        $user = User::factory()->create(['role' => UserRole::COURIER]);
        $courier = Courier::create(['user_id' => $user->id]);
        $order = fn () => Order::factory()->create(['address' => ['address' => 'ул. Битарап 10', 'phone' => '+99365000000']]);

        $today = CourierEarning::create(['order_id' => $order()->id, 'courier_id' => $courier->id, 'amount' => 15, 'status' => 'pending']);
        $old = CourierEarning::create(['order_id' => $order()->id, 'courier_id' => $courier->id, 'amount' => 20, 'status' => 'paid']);
        $old->forceFill(['created_at' => now()->subMonths(2)])->save();

        $response = $this->actingAs($user)->getJson('/api/v1/courier/earnings')->assertOk();

        $response->assertJsonPath('summary.today', 15)
            ->assertJsonPath('summary.month', 15)
            ->assertJsonPath('summary.unpaid', 15)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $today->id);
        $this->assertStringNotContainsString('99365000000', $response->getContent());

        // Someone else's earnings are not visible
        $other = User::factory()->create(['role' => UserRole::COURIER]);
        Courier::create(['user_id' => $other->id]);
        $this->actingAs($other)->getJson('/api/v1/courier/earnings')->assertJsonCount(0, 'data');
    }

    #[Group('courier')]
    public function test_other_roles_cannot_use_the_courier_api(): void
    {
        $client = User::factory()->create();

        foreach (['/api/v1/courier/orders', '/api/v1/courier/offers', '/api/v1/courier/earnings'] as $url) {
            $this->actingAs($client)->getJson($url)->assertStatus(403);
        }
        $this->actingAs($client)->postJson('/api/v1/courier/status', ['status' => 'available'])->assertStatus(403);
    }

    #[Group('courier')]
    public function test_available_order_hides_the_customer_until_it_is_accepted(): void
    {
        $user = User::factory()->create(['role' => UserRole::COURIER]);
        Courier::create(['user_id' => $user->id]);

        $order = Order::factory()->create([
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'comment' => 'Код домофона 4321',
            'address' => [
                'address' => 'ул. Битарап 10',
                'lat' => 39.09,
                'lon' => 63.56,
                'name' => 'Айгуль',
                'phone' => '+99365000000',
                'apartment' => '17',
                'courier_comment' => 'Звонить дважды',
            ],
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/courier/orders')->assertOk();

        $response->assertJsonPath('data.available.0.address.address', 'ул. Битарап 10');
        $body = $response->getContent();
        foreach (['99365000000', 'Айгуль', '4321', 'Звонить', 'apartment'] as $private) {
            $this->assertStringNotContainsString($private, json_encode(json_decode($body), JSON_UNESCAPED_UNICODE));
        }

        // Once accepted, the courier gets the full order
        $this->actingAs($user)->postJson("/api/v1/courier/orders/{$order->id}/accept")->assertOk();
        $this->actingAs($user)->getJson('/api/v1/courier/orders')
            ->assertJsonPath('data.active.0.address.phone', '+99365000000');
    }

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
            'status' => 'available',
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
    public function test_courier_earns_the_whole_delivery_fee(): void
    {
        // Business rule: the courier gets the whole delivery fee the customer paid
        // (the restaurant's price for the district); courier_fixed/percent fees no longer apply.
        $restaurant = Restaurant::factory()->create([
            'courier_fixed_fee' => 10,
            'courier_percent_fee' => 5,
        ]);

        $user = User::factory()->create(['role' => UserRole::COURIER]);
        $courier = Courier::create(['user_id' => $user->id]);

        $order = Order::factory()->create([
            'vendor_id' => $restaurant->id,
            'courier_id' => $courier->id,
            'status' => Order::STATUS_DELIVERING,
            'total' => 1000.00,
            'delivery_fee' => 15.00,
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/courier/orders/{$order->id}/status", [
            'status' => Order::STATUS_DELIVERED,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('courier_earnings', [
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'amount' => 15.00,
        ]);

        $earningsResponse = $this->actingAs($user)->getJson('/api/v1/courier/earnings');
        $earningsResponse->assertStatus(200);
        $earningsResponse->assertJsonPath('data.0.amount', 15);
    }
}
