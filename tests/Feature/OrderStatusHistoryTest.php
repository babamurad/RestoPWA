<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderStatusHistory;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->restaurant = Restaurant::factory()->create();
        $this->restaurant->update(['vendor_id' => $this->restaurant->id]);
        $this->user = User::factory()->create();
    }

    public function test_creates_status_history_on_status_change(): void
    {
        $order = Order::create([
            'vendor_id' => $this->restaurant->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'address' => ['address' => 'Test St'],
            'items' => [],
            'total' => 1000,
        ]);

        $order->update(['status' => 'processing']);

        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'from_status' => 'pending',
            'to_status' => 'processing',
        ]);
    }

    public function test_creates_history_with_correct_metadata(): void
    {
        $order = Order::create([
            'vendor_id' => $this->restaurant->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'address' => [],
            'items' => [],
            'total' => 500,
        ]);

        $order->update(['status' => 'shipped']);

        $history = OrderStatusHistory::where('order_id', $order->id)->first();

        $this->assertNotNull($history);
        $this->assertArrayHasKey('changed_by', $history->metadata);
    }

    public function test_no_history_created_when_status_unchanged(): void
    {
        $order = Order::create([
            'vendor_id' => $this->restaurant->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'address' => [],
            'items' => [],
            'total' => 500,
        ]);

        $order->update(['status' => 'pending']);

        $count = OrderStatusHistory::where('order_id', $order->id)->count();

        $this->assertSame(0, $count);
    }

    public function test_multiple_status_changes_create_multiple_history_records(): void
    {
        $order = Order::create([
            'vendor_id' => $this->restaurant->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'address' => [],
            'items' => [],
            'total' => 500,
        ]);

        $order->update(['status' => 'processing']);
        $order->update(['status' => 'shipped']);
        $order->update(['status' => 'delivered']);

        $history = OrderStatusHistory::where('order_id', $order->id)
            ->orderBy('created_at')
            ->get();

        $this->assertCount(3, $history);
        $this->assertSame('processing', $history[0]->to_status);
        $this->assertSame('shipped', $history[1]->to_status);
        $this->assertSame('delivered', $history[2]->to_status);
    }

    public function test_order_status_history_belongs_to_order(): void
    {
        $order = Order::create([
            'vendor_id' => $this->restaurant->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'address' => [],
            'items' => [],
            'total' => 500,
        ]);

        $order->update(['status' => 'completed']);

        $history = OrderStatusHistory::where('order_id', $order->id)->first();

        $this->assertTrue($history->order->is($order));
    }

    public function test_money_cast_on_order_total(): void
    {
        $order = Order::create([
            'vendor_id' => $this->restaurant->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'address' => [],
            'items' => [],
            'total' => 2500,
        ]);

        $this->assertSame(25.0, $order->total);
    }
}
