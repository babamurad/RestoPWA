<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Order\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Orders get a short sequential number (#1001, #1002…). The old "#" + first
 * 8 chars of the UUIDv7 repeated for orders placed within the same minute.
 */
class OrderNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_get_unique_sequential_numbers(): void
    {
        $orders = Order::factory()->count(5)->create();

        $numbers = $orders->pluck('number')->all();
        $this->assertSame([1001, 1002, 1003, 1004, 1005], $numbers);
        $this->assertSame('1003', $orders[2]->order_number);

        // UUIDv7 prefixes of orders created together collide; numbers don't
        $this->assertCount(1, $orders->map(fn (Order $o) => substr($o->id, 0, 6))->unique());
    }

    public function test_number_cannot_be_mass_assigned(): void
    {
        $order = (new Order)->fill(['number' => 1, 'status' => Order::STATUS_PENDING]);

        $this->assertNull($order->number);
        $this->assertSame(Order::STATUS_PENDING, $order->status);
    }

    public function test_numbering_continues_after_the_stored_counter(): void
    {
        DB::table('sequences')->where('name', 'orders')->update(['value' => 5000]);

        $this->assertSame(5001, Order::factory()->create()->number);
    }

    public function test_customer_sees_the_number_in_the_app_and_on_the_success_page(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->getJson('/api/v1/orders')
            ->assertJsonPath('data.0.number', (string) $order->number);

        $this->actingAs($user)->get(route('order.success', $order->id))
            ->assertOk()
            ->assertSee('#'.$order->number);

        $this->actingAs($user)->get(route('order.track', $order->id))
            ->assertOk()
            ->assertSee((string) $order->number);
    }

    public function test_success_page_follows_the_dark_theme(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('order.success', $order->id))
            ->assertSee("localStorage.getItem('theme')", false)
            ->assertSee('dark:bg-slate-950', false);
    }
}
