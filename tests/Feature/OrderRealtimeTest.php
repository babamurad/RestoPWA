<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\UserRole;
use App\Events\OrderStatusUpdated;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

/**
 * Realtime order tracking: status changes are pushed to private channels
 * that only the right people can join.
 */
class OrderRealtimeTest extends TestCase
{
    use RefreshDatabase;

    private function useReverbBroadcaster(): void
    {
        // Channel auth is signed locally; no websocket server is contacted.
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
            'broadcasting.connections.reverb.options.host' => 'localhost',
        ]);

        // Channels were registered on the boot-time broadcaster; register them on this one.
        require base_path('routes/channels.php');
    }

    private function authorize(User $user, string $channel): TestResponse
    {
        return $this->actingAs($user)->post('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-'.$channel,
        ]);
    }

    public function test_status_change_is_broadcast_to_order_and_restaurant_channels(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);

        Broadcast::shouldReceive('queue')->once()->with(Mockery::on(function ($event) use ($order) {
            $channels = collect($event->broadcastOn())->map(fn ($c) => $c->name)->all();

            return $event instanceof OrderStatusUpdated
                && $event->status === Order::STATUS_CONFIRMED
                && $event->broadcastAs() === 'order.status.updated'
                && $channels === ['private-order.'.$order->id, 'private-restaurant.'.$order->vendor_id];
        }));

        $order->update(['status' => Order::STATUS_CONFIRMED]);
    }

    public function test_status_broadcast_waits_for_transaction_commit(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);

        $committed = false;
        Broadcast::shouldReceive('queue')->once()->with(Mockery::on(function () use (&$committed) {
            // Must not fire while the transaction is still open
            return $committed;
        }));

        DB::transaction(function () use ($order, &$committed) {
            $order->update(['status' => Order::STATUS_CONFIRMED]);
            $committed = true; // set right before the transaction closure returns
        });
    }

    public function test_status_broadcast_is_dropped_on_rollback(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);

        Broadcast::shouldReceive('queue')->never();

        try {
            DB::transaction(function () use ($order) {
                $order->update(['status' => Order::STATUS_CONFIRMED]);
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException) {
        }

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_broadcast_failure_does_not_block_status_change(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);

        Broadcast::shouldReceive('queue')->andThrow(new BroadcastException('Reverb is down'));

        $order->update(['status' => Order::STATUS_CONFIRMED]);

        $this->assertSame(Order::STATUS_CONFIRMED, $order->fresh()->status);
    }

    public function test_order_channel_is_open_to_its_customer_admin_and_restaurant_owner_only(): void
    {
        $customer = User::factory()->create(['role' => UserRole::CLIENT]);
        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);
        $order = Order::factory()->create(['vendor_id' => $restaurant->id, 'user_id' => $customer->id]);

        $this->useReverbBroadcaster();

        $this->authorize($customer, 'order.'.$order->id)->assertOk();
        $this->authorize(User::factory()->create(['role' => UserRole::ADMIN]), 'order.'.$order->id)->assertOk();
        $this->authorize($owner, 'order.'.$order->id)->assertOk();

        $otherClient = User::factory()->create(['role' => UserRole::CLIENT]);
        $this->authorize($otherClient, 'order.'.$order->id)->assertForbidden();

        $foreignOwner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        Restaurant::factory()->create(['owner_id' => $foreignOwner->id]);
        $this->authorize($foreignOwner, 'order.'.$order->id)->assertForbidden();
    }

    public function test_restaurant_channel_is_open_to_its_owner_and_admin_only(): void
    {
        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);

        $this->useReverbBroadcaster();

        $this->authorize($owner, 'restaurant.'.$restaurant->id)->assertOk();
        $this->authorize(User::factory()->create(['role' => UserRole::ADMIN]), 'restaurant.'.$restaurant->id)->assertOk();

        $foreignOwner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        Restaurant::factory()->create(['owner_id' => $foreignOwner->id]);
        $this->authorize($foreignOwner, 'restaurant.'.$restaurant->id)->assertForbidden();
        $this->authorize(User::factory()->create(['role' => UserRole::CLIENT]), 'restaurant.'.$restaurant->id)->assertForbidden();
    }
}
