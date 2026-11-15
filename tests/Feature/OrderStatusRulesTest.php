<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Order\Exceptions\InvalidStatusTransition;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Services\OrderService;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\UserRole;
use App\Jobs\SendOrderStatusNotification;
use App\Livewire\Order\KanbanBoard;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * One dictionary of order statuses, steps only in order, one history record per step.
 */
class OrderStatusRulesTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();

        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $this->restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        Filament::setTenant($this->restaurant);
    }

    private function order(string $status, string $deliveryType = Order::DELIVERY_TYPE_DELIVERY, array $extra = []): Order
    {
        return Order::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'delivery_type' => $deliveryType,
            'status' => $status,
        ] + $extra);
    }

    public function test_kanban_move_writes_exactly_one_history_record(): void
    {
        $order = $this->order(Order::STATUS_PENDING);

        Livewire::test(KanbanBoard::class)->call('moveOrder', $order->id, 'cooking');

        $history = $order->statusHistory()->get();
        $this->assertCount(1, $history);
        $this->assertSame(Order::STATUS_PENDING, $history[0]->from_status);
        $this->assertSame(Order::STATUS_PREPARING, $history[0]->to_status);
        $this->assertSame('kanban', $history[0]->metadata['changed_via']);
    }

    public function test_kanban_cooking_column_sets_preparing_and_notifies_the_customer(): void
    {
        Queue::fake([SendOrderStatusNotification::class]);
        $order = $this->order(Order::STATUS_PENDING);

        Livewire::test(KanbanBoard::class)->call('moveOrder', $order->id, 'cooking');

        $this->assertSame(Order::STATUS_PREPARING, $order->fresh()->status);
        Queue::assertPushed(SendOrderStatusNotification::class, fn ($job) => $job->status === Order::STATUS_PREPARING);
    }

    public function test_confirmed_order_moves_on_to_preparing(): void
    {
        $order = $this->order(Order::STATUS_CONFIRMED);

        Livewire::test(KanbanBoard::class)->call('moveOrder', $order->id, 'ready');
        $this->assertSame(Order::STATUS_CONFIRMED, $order->fresh()->status, 'confirmed → ready skips the kitchen');

        app(OrderService::class)->updateStatus($order->id, Order::STATUS_PREPARING);
        $this->assertSame(Order::STATUS_PREPARING, $order->fresh()->status);
    }

    #[DataProvider('forbiddenKanbanMoves')]
    public function test_kanban_refuses_moves_back_or_over_a_step(string $from, string $column, string $deliveryType): void
    {
        $order = $this->order($from, $deliveryType);

        Livewire::test(KanbanBoard::class)->call('moveOrder', $order->id, $column);

        $this->assertSame($from, $order->fresh()->status);
        $this->assertCount(0, $order->statusHistory()->get());
        Notification::assertNotified('Нельзя переместить заказ');
    }

    public static function forbiddenKanbanMoves(): array
    {
        return [
            'new straight to ready' => [Order::STATUS_PENDING, 'ready', Order::DELIVERY_TYPE_DELIVERY],
            'new straight to delivery' => [Order::STATUS_PENDING, 'delivering', Order::DELIVERY_TYPE_DELIVERY],
            'cooking back to new' => [Order::STATUS_PREPARING, 'pending', Order::DELIVERY_TYPE_DELIVERY],
            'ready back to cooking' => [Order::STATUS_READY_FOR_PICKUP, 'cooking', Order::DELIVERY_TYPE_DELIVERY],
            'delivered back to new' => [Order::STATUS_DELIVERED, 'pending', Order::DELIVERY_TYPE_DELIVERY],
            'delivered back to delivery' => [Order::STATUS_DELIVERED, 'delivering', Order::DELIVERY_TYPE_DELIVERY],
            'pickup cooking straight to done' => [Order::STATUS_PREPARING, 'completed', Order::DELIVERY_TYPE_PICKUP],
            'handed over pickup back to ready' => [Order::STATUS_COMPLETED, 'ready', Order::DELIVERY_TYPE_PICKUP],
        ];
    }

    public function test_service_rejects_a_forbidden_step(): void
    {
        $order = $this->order(Order::STATUS_DELIVERED);

        $this->expectException(InvalidStatusTransition::class);

        app(OrderService::class)->updateStatus($order->id, Order::STATUS_PENDING);
    }

    public function test_form_offers_only_the_current_status_and_the_next_steps(): void
    {
        $this->assertSame(
            [Order::STATUS_PREPARING, Order::STATUS_READY_FOR_PICKUP, Order::STATUS_CANCELLED],
            array_keys($this->order(Order::STATUS_PREPARING)->selectableStatusLabels()),
        );

        $this->assertSame(
            [Order::STATUS_READY_FOR_PICKUP, Order::STATUS_COMPLETED, Order::STATUS_CANCELLED],
            array_keys($this->order(Order::STATUS_READY_FOR_PICKUP, Order::DELIVERY_TYPE_PICKUP)->selectableStatusLabels()),
        );

        // A finished order offers nothing but its own status
        $this->assertSame(
            [Order::STATUS_DELIVERED],
            array_keys($this->order(Order::STATUS_DELIVERED)->selectableStatusLabels()),
        );
    }

    public function test_kanban_shows_orders_in_work_and_only_recently_finished_ones(): void
    {
        $active = $this->order(Order::STATUS_PREPARING, extra: ['created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)]);
        $recent = $this->order(Order::STATUS_DELIVERED, extra: ['updated_at' => now()->subHours(2)]);
        $old = $this->order(Order::STATUS_DELIVERED, extra: ['updated_at' => now()->subDays(2)]);

        $columns = Livewire::test(KanbanBoard::class)->get('columns');
        $shown = collect($columns)->flatMap(fn ($column) => array_column($column['orders'], 'id'))->all();

        $this->assertContains($active->id, $shown);
        $this->assertContains($recent->id, $shown);
        $this->assertNotContains($old->id, $shown);
    }

    public function test_migration_renames_cooking_and_removes_duplicate_history(): void
    {
        $order = $this->order(Order::STATUS_PENDING);
        DB::table('orders')->where('id', $order->id)->update(['status' => 'cooking']);

        $row = fn (string $from, string $to, string $at, array $meta = []) => [
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'from_status' => $from,
            'to_status' => $to,
            'metadata' => json_encode($meta),
            'created_at' => $at,
        ];

        DB::table('order_status_history')->insert([
            // one kanban move, written twice
            $row('pending', 'cooking', '2026-10-01 10:00:00', ['changed_by' => 'u1']),
            $row('pending', 'cooking', '2026-10-01 10:00:00', ['changed_via' => 'kanban', 'changed_by' => 'u1', 'new_column' => 'cooking']),
            // the same step again an hour later is a real, separate change
            $row('pending', 'cooking', '2026-10-01 11:00:00'),
            $row('cooking', 'ready_for_pickup', '2026-10-01 11:30:00'),
        ]);

        (require database_path('migrations/2026_11_15_000001_unify_order_statuses.php'))->up();

        $this->assertSame(Order::STATUS_PREPARING, $order->fresh()->status);

        $history = DB::table('order_status_history')->where('order_id', $order->id)->orderBy('created_at')->get();
        $this->assertCount(3, $history);
        $this->assertSame(0, $history->filter(fn ($h) => $h->from_status === 'cooking' || $h->to_status === 'cooking')->count());
        $this->assertStringContainsString('kanban', $history[0]->metadata, 'the richer copy is the one kept');
    }
}
