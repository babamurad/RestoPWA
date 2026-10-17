<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\CourierOffer;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\UserRole;
use App\Filament\Resources\CourierResource\Pages\EditCourier;
use App\Filament\Resources\CourierResource\Pages\ListCouriers;
use App\Livewire\Order\KanbanBoard;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Automatic dispatch: a ready delivery order is offered to one courier at a
 * time — bound couriers first, then the general pool; the longest-waiting
 * courier first; declined/expired offers pass to the next courier.
 */
class CourierDispatchTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::factory()->create();
    }

    private function courier(array $restaurants = [], string $status = Courier::STATUS_AVAILABLE, ?string $lastAssigned = null): Courier
    {
        $courier = Courier::create([
            'user_id' => User::factory()->create(['role' => UserRole::COURIER])->id,
            'status' => $status,
            'last_assigned_at' => $lastAssigned,
        ]);
        $courier->restaurants()->attach(collect($restaurants)->pluck('id'));

        return $courier;
    }

    private function readyOrder(array $attributes = []): Order
    {
        $order = Order::factory()->create(['vendor_id' => $this->restaurant->id, 'status' => Order::STATUS_PREPARING] + $attributes);
        $order->update(['status' => Order::STATUS_READY_FOR_PICKUP]);

        return $order;
    }

    private function pendingOffer(Order $order): ?CourierOffer
    {
        return CourierOffer::where('order_id', $order->id)->where('status', CourierOffer::STATUS_PENDING)->first();
    }

    public function test_bound_courier_gets_the_offer_before_the_general_pool(): void
    {
        $pool = $this->courier(lastAssigned: '2020-01-01 00:00:00'); // waited longest, but general pool
        $bound = $this->courier([$this->restaurant], lastAssigned: now()->subMinute()->toDateTimeString());

        $order = $this->readyOrder();

        $this->assertSame($bound->id, $this->pendingOffer($order)->courier_id);
        $this->assertNotSame($pool->id, $this->pendingOffer($order)->courier_id);
    }

    public function test_longest_waiting_courier_is_offered_first(): void
    {
        $recent = $this->courier([$this->restaurant], lastAssigned: now()->subMinutes(5)->toDateTimeString());
        $waiting = $this->courier([$this->restaurant], lastAssigned: now()->subHours(2)->toDateTimeString());

        $order = $this->readyOrder();

        $this->assertSame($waiting->id, $this->pendingOffer($order)->courier_id);
        $this->assertNotSame($recent->id, $this->pendingOffer($order)->courier_id);
    }

    public function test_unavailable_busy_and_foreign_couriers_are_skipped(): void
    {
        $this->courier([$this->restaurant], Courier::STATUS_OFFLINE);
        $this->courier([$this->restaurant], Courier::STATUS_BUSY);
        $this->courier([Restaurant::factory()->create()]); // bound to another restaurant
        $onDelivery = $this->courier([$this->restaurant]);
        Order::factory()->create(['courier_id' => $onDelivery->id, 'status' => Order::STATUS_DELIVERING]);
        $free = $this->courier();

        $order = $this->readyOrder();

        $this->assertSame($free->id, $this->pendingOffer($order)->courier_id);
    }

    public function test_declined_offer_goes_to_the_next_courier_and_never_back(): void
    {
        $first = $this->courier([$this->restaurant], lastAssigned: now()->subHours(2)->toDateTimeString());
        $second = $this->courier([$this->restaurant], lastAssigned: now()->subHour()->toDateTimeString());
        $order = $this->readyOrder();

        $this->actingAs($first->user)
            ->postJson('/api/v1/courier/offers/'.$this->pendingOffer($order)->id.'/decline')
            ->assertOk();
        $this->assertSame($second->id, $this->pendingOffer($order)->courier_id);

        $this->actingAs($second->user)
            ->postJson('/api/v1/courier/offers/'.$this->pendingOffer($order)->id.'/decline')
            ->assertOk();

        // Everybody declined: the order waits for manual assignment
        $this->assertNull($this->pendingOffer($order));
        $this->assertNull($order->fresh()->courier_id);
        $this->assertSame(Order::STATUS_READY_FOR_PICKUP, $order->fresh()->status);
    }

    public function test_unanswered_offer_expires_and_passes_on(): void
    {
        $first = $this->courier([$this->restaurant], lastAssigned: now()->subHours(2)->toDateTimeString());
        $second = $this->courier([$this->restaurant], lastAssigned: now()->subHour()->toDateTimeString());
        $order = $this->readyOrder();
        $firstOffer = $this->pendingOffer($order);

        $this->travel(config('logistics.offer_timeout_seconds') + 1)->seconds();
        $this->artisan('couriers:expire-offers')->assertSuccessful();

        $this->assertSame(CourierOffer::STATUS_EXPIRED, $firstOffer->fresh()->status);
        $this->assertSame($second->id, $this->pendingOffer($order)->courier_id);

        // Too late to accept the expired offer
        $this->actingAs($first->user)
            ->postJson("/api/v1/courier/offers/{$firstOffer->id}/accept")
            ->assertStatus(409);
    }

    public function test_courier_accepts_offer(): void
    {
        $courier = $this->courier([$this->restaurant]);
        $order = $this->readyOrder();
        $offer = $this->pendingOffer($order);

        $this->actingAs($courier->user)
            ->getJson('/api/v1/courier/offers')
            ->assertOk()
            ->assertJsonPath('data.0.offer_id', $offer->id);

        $this->actingAs($courier->user)
            ->postJson("/api/v1/courier/offers/{$offer->id}/accept")
            ->assertOk();

        $order->refresh();
        $this->assertSame($courier->id, $order->courier_id);
        $this->assertSame(Order::STATUS_DELIVERING, $order->status);
        $this->assertSame(CourierOffer::STATUS_ACCEPTED, $offer->fresh()->status);
        $this->assertNotNull($courier->fresh()->last_assigned_at);
    }

    public function test_courier_cannot_accept_someone_elses_offer(): void
    {
        $this->courier([$this->restaurant]);
        $stranger = $this->courier([$this->restaurant], Courier::STATUS_OFFLINE);
        $order = $this->readyOrder();

        $this->actingAs($stranger->user)
            ->postJson('/api/v1/courier/offers/'.$this->pendingOffer($order)->id.'/accept')
            ->assertStatus(403);

        $this->assertNull($order->fresh()->courier_id);
    }

    public function test_manual_assignment_cancels_the_pending_offer(): void
    {
        $this->courier([$this->restaurant]);
        $other = $this->courier([$this->restaurant], Courier::STATUS_OFFLINE);
        $order = $this->readyOrder();
        $offer = $this->pendingOffer($order);

        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]))
            ->postJson("/api/v1/orders/{$order->id}/assign", ['courier_id' => $other->id])
            ->assertOk();

        $this->assertSame(CourierOffer::STATUS_CANCELLED, $offer->fresh()->status);
    }

    public function test_pickup_orders_are_not_dispatched(): void
    {
        $this->courier([$this->restaurant]);

        $order = $this->readyOrder(['delivery_type' => Order::DELIVERY_TYPE_PICKUP]);

        $this->assertSame(0, CourierOffer::where('order_id', $order->id)->count());
    }

    public function test_kanban_ready_column_makes_order_visible_and_offered_to_couriers(): void
    {
        // Regression: the kanban used to set "ready", which couriers never saw
        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $this->restaurant->update(['owner_id' => $owner->id]);
        $courier = $this->courier([$this->restaurant]);
        $order = Order::factory()->create(['vendor_id' => $this->restaurant->id, 'status' => Order::STATUS_PREPARING]);

        $this->actingAs($owner);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        Filament::setTenant($this->restaurant);
        Livewire::test(KanbanBoard::class)->call('moveOrder', $order->id, 'ready');

        $this->assertSame(Order::STATUS_READY_FOR_PICKUP, $order->fresh()->status);
        $this->assertSame($courier->id, $this->pendingOffer($order)->courier_id);
        $this->actingAs($courier->user)
            ->getJson('/api/v1/courier/orders')
            ->assertJsonPath('data.available.0.id', $order->id);
    }

    public function test_courier_bound_to_several_restaurants_sees_all_their_orders(): void
    {
        $second = Restaurant::factory()->create();
        $foreign = Restaurant::factory()->create();
        $courier = $this->courier([$this->restaurant, $second], Courier::STATUS_OFFLINE);
        $a = Order::factory()->create(['vendor_id' => $this->restaurant->id, 'status' => Order::STATUS_READY_FOR_PICKUP]);
        $b = Order::factory()->create(['vendor_id' => $second->id, 'status' => Order::STATUS_READY_FOR_PICKUP]);
        $c = Order::factory()->create(['vendor_id' => $foreign->id, 'status' => Order::STATUS_READY_FOR_PICKUP]);

        $ids = collect($this->actingAs($courier->user)->getJson('/api/v1/courier/orders')->json('data.available'))->pluck('id');

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $ids->all());
        $this->assertNotContains($c->id, $ids);
    }

    public function test_admin_binds_courier_to_several_restaurants(): void
    {
        $second = Restaurant::factory()->create();
        $courier = $this->courier();

        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ListCouriers::class)->assertCanSeeTableRecords([$courier]);

        Livewire::test(EditCourier::class, ['record' => $courier->getRouteKey()])
            ->fillForm(['restaurants' => [$this->restaurant->id, $second->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEqualsCanonicalizing([$this->restaurant->id, $second->id], $courier->fresh()->restaurantIds());
    }
}
