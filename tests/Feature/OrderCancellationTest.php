<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\CourierOffer;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Services\OrderService;
use App\Domains\Payment\Services\PaymentService;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\OrderCancelReason;
use App\Enums\UserRole;
use App\Jobs\SendOrderStatusNotification;
use App\Jobs\SendSmsJob;
use App\Livewire\Order\KanbanBoard;
use App\Models\User;
use App\Services\PushNotificationService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::factory()->create();
        $this->customer = User::factory()->create(['phone' => '+99361234567']);
    }

    private function order(string $status, array $extra = []): Order
    {
        return Order::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'user_id' => $this->customer->id,
            'status' => $status,
        ] + $extra);
    }

    #[DataProvider('cancellableStatuses')]
    public function test_customer_cancels_an_order_the_kitchen_has_not_started(string $status): void
    {
        $order = $this->order($status);

        $this->actingAs($this->customer)
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', Order::STATUS_CANCELLED);

        $order->refresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertSame(Order::CANCELLED_BY_CUSTOMER, $order->cancelled_by);
        $this->assertSame(OrderCancelReason::CHANGED_MIND, $order->cancel_reason);
        $this->assertNotNull($order->cancelled_at);
        $this->assertCount(1, $order->statusHistory()->where('to_status', Order::STATUS_CANCELLED)->get());
    }

    public static function cancellableStatuses(): array
    {
        return [[Order::STATUS_PENDING], [Order::STATUS_CONFIRMED]];
    }

    #[DataProvider('lockedStatuses')]
    public function test_customer_cannot_cancel_once_the_kitchen_has_started(string $status): void
    {
        $order = $this->order($status);

        $this->actingAs($this->customer)
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertStatus(422)
            ->assertJsonPath('message', __('api.orders.cannot_cancel'));

        $this->assertSame($status, $order->fresh()->status);
    }

    public static function lockedStatuses(): array
    {
        return [
            [Order::STATUS_PREPARING],
            [Order::STATUS_READY_FOR_PICKUP],
            [Order::STATUS_DELIVERING],
            [Order::STATUS_DELIVERED],
            [Order::STATUS_CANCELLED],
        ];
    }

    public function test_customer_cannot_cancel_someone_elses_order(): void
    {
        $order = $this->order(Order::STATUS_PENDING);

        $this->actingAs(User::factory()->create())
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertStatus(403);

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_guest_cannot_cancel_an_order(): void
    {
        $order = $this->order(Order::STATUS_PENDING);

        $this->postJson("/api/v1/orders/{$order->id}/cancel")->assertStatus(401);

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_orders_list_tells_the_app_what_can_be_cancelled_and_why_it_was(): void
    {
        $open = $this->order(Order::STATUS_PENDING);
        $cooking = $this->order(Order::STATUS_PREPARING);
        $declined = $this->order(Order::STATUS_PREPARING);
        app(OrderService::class)->cancel($declined, Order::CANCELLED_BY_RESTAURANT, OrderCancelReason::OUT_OF_STOCK);

        $orders = collect($this->actingAs($this->customer)->getJson('/api/v1/orders')->assertOk()->json('data'))->keyBy('id');

        $this->assertTrue($orders[$open->id]['can_cancel']);
        $this->assertFalse($orders[$cooking->id]['can_cancel']);
        $this->assertNull($orders[$cooking->id]['cancel_reason']);
        $this->assertSame(__('notifications.cancel_reasons.out_of_stock'), $orders[$declined->id]['cancel_reason']);
    }

    public function test_restaurant_declines_from_the_kanban_with_a_reason(): void
    {
        Queue::fake([SendOrderStatusNotification::class]);
        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $this->restaurant->update(['owner_id' => $owner->id]);
        $order = $this->order(Order::STATUS_PREPARING);

        $this->actingAs($owner);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        Filament::setTenant($this->restaurant);

        Livewire::test(KanbanBoard::class)->call('declineOrder', $order->id, OrderCancelReason::TOO_BUSY->value);

        $order->refresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertSame(Order::CANCELLED_BY_RESTAURANT, $order->cancelled_by);
        $this->assertSame(OrderCancelReason::TOO_BUSY, $order->cancel_reason);
        Queue::assertPushed(SendOrderStatusNotification::class, fn ($job) => $job->status === Order::STATUS_CANCELLED);
    }

    public function test_kanban_decline_ignores_other_restaurants_orders_and_unknown_reasons(): void
    {
        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $this->restaurant->update(['owner_id' => $owner->id]);
        $foreign = Order::factory()->create(['vendor_id' => Restaurant::factory()->create()->id, 'status' => Order::STATUS_PENDING]);
        $own = $this->order(Order::STATUS_PENDING);

        $this->actingAs($owner);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        Filament::setTenant($this->restaurant);

        Livewire::test(KanbanBoard::class)
            ->call('declineOrder', $foreign->id, OrderCancelReason::TOO_BUSY->value)
            ->call('declineOrder', $own->id, 'made_up_reason')
            ->call('declineOrder', $own->id, OrderCancelReason::CHANGED_MIND->value);

        $this->assertSame(Order::STATUS_PENDING, $foreign->fresh()->status);
        $this->assertSame(Order::STATUS_PENDING, $own->fresh()->status);
    }

    public function test_cancelling_closes_courier_offers_and_the_pending_payment(): void
    {
        $order = $this->order(Order::STATUS_READY_FOR_PICKUP);
        app(PaymentService::class)->start($order);
        $courier = Courier::create(['user_id' => User::factory()->create(['role' => UserRole::COURIER])->id]);
        $offer = CourierOffer::create([
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'status' => CourierOffer::STATUS_PENDING,
            'expires_at' => now()->addSeconds(30),
        ]);

        app(OrderService::class)->cancel($order, Order::CANCELLED_BY_RESTAURANT, OrderCancelReason::CLOSING);

        $this->assertSame(CourierOffer::STATUS_CANCELLED, $offer->fresh()->status);
        $this->assertSame(0, $order->paymentTransactions()->where('status', 'pending')->count());
    }

    public function test_customer_is_told_the_reason_by_push_and_sms(): void
    {
        Queue::fake([SendSmsJob::class]);
        $order = $this->order(Order::STATUS_PREPARING);
        app(OrderService::class)->cancel($order, Order::CANCELLED_BY_RESTAURANT, OrderCancelReason::OUT_OF_STOCK);

        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('sendToUser')->once()
            ->withArgs(fn ($userId, $title, $body) => str_contains($body, __('notifications.cancel_reasons.out_of_stock')))
            ->andReturn(1);
        $this->app->instance(PushNotificationService::class, $push);

        (new SendOrderStatusNotification($order->id, Order::STATUS_CANCELLED))->handle();

        Queue::assertPushed(SendSmsJob::class);
    }

    public function test_customer_who_cancelled_himself_gets_no_message(): void
    {
        Queue::fake([SendSmsJob::class]);
        $order = $this->order(Order::STATUS_PENDING);
        app(OrderService::class)->cancel($order, Order::CANCELLED_BY_CUSTOMER, OrderCancelReason::CHANGED_MIND);

        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldNotReceive('sendToUser');
        $this->app->instance(PushNotificationService::class, $push);

        (new SendOrderStatusNotification($order->id, Order::STATUS_CANCELLED))->handle();

        Queue::assertNotPushed(SendSmsJob::class);
    }

    public function test_other_reason_shows_the_restaurants_own_words(): void
    {
        $order = $this->order(Order::STATUS_PENDING);
        app(OrderService::class)->cancel($order, Order::CANCELLED_BY_RESTAURANT, OrderCancelReason::OTHER, ' Отключили свет ');

        $this->assertSame('Отключили свет', $order->fresh()->cancelReasonText());
    }
}
