<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\UserRole;
use App\Livewire\Order\KanbanBoard;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The kanban must not finish delivery orders past the courier, nor send pickups out for delivery.
 */
class KanbanMoveRulesTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();

        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $this->restaurant = Restaurant::factory()->create(['owner_id' => $owner->id, 'commission_percent' => 10]);

        $this->actingAs($owner);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        Filament::setTenant($this->restaurant);
    }

    private function order(string $deliveryType, string $status = Order::STATUS_READY_FOR_PICKUP): Order
    {
        return Order::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'delivery_type' => $deliveryType,
            'status' => $status,
            'total' => 100.0,
        ]);
    }

    public function test_delivery_order_cannot_be_completed_on_the_kanban(): void
    {
        $order = $this->order(Order::DELIVERY_TYPE_DELIVERY, Order::STATUS_DELIVERING);

        Livewire::test(KanbanBoard::class)->call('moveOrder', $order->id, 'completed');

        $this->assertSame(Order::STATUS_DELIVERING, $order->fresh()->status);
        $this->assertEquals(0, $order->fresh()->commission_amount);
        Notification::assertNotified('Нельзя переместить заказ');
    }

    public function test_pickup_order_cannot_go_out_for_delivery(): void
    {
        $order = $this->order(Order::DELIVERY_TYPE_PICKUP);

        Livewire::test(KanbanBoard::class)->call('moveOrder', $order->id, 'delivering');

        $this->assertSame(Order::STATUS_READY_FOR_PICKUP, $order->fresh()->status);
        Notification::assertNotified('Нельзя переместить заказ');
    }

    public function test_pickup_order_is_handed_over_on_the_kanban(): void
    {
        $order = $this->order(Order::DELIVERY_TYPE_PICKUP);

        Livewire::test(KanbanBoard::class)->call('moveOrder', $order->id, 'completed');

        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->status);
        $this->assertEquals(10.0, $order->fresh()->commission_amount);
    }

    public function test_delivery_order_still_moves_through_the_kitchen_columns(): void
    {
        $order = $this->order(Order::DELIVERY_TYPE_DELIVERY, Order::STATUS_PENDING);

        Livewire::test(KanbanBoard::class)->call('moveOrder', $order->id, 'cooking');

        $this->assertNotSame(Order::STATUS_PENDING, $order->fresh()->status);
    }
}
