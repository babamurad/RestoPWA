<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Geo\Services\DeliveryZoneCheckResult;
use App\Domains\Geo\Services\GeoService;
use App\Domains\Menu\Models\Product;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Services\OrderService;
use App\Domains\Payment\Models\PaymentTransaction;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\OrderResource\Pages\ManageOrders;
use App\Filament\Vendor\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Vendor\Resources\OrderResource\Pages\ListOrders;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

/**
 * Every order goes through payment_transactions, even cash: open on creation,
 * collected on delivery/pickup completion, closed on cancellation.
 */
class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::factory()->create(['is_active' => true, 'delivery_fee' => 12.00, 'min_order' => 0]);
        $this->restaurant->update(['vendor_id' => $this->restaurant->id]);
    }

    private function createOrder(string $paymentMethod = 'cash'): Order
    {
        return app(OrderService::class)->createOrder([
            'vendor_id' => $this->restaurant->id,
            'user_id' => User::factory()->create()->id,
            'items' => [['product_id' => 'p1', 'product_name' => 'Плов', 'quantity' => 1, 'unit_price' => 10000, 'total_price' => 10000]],
            'total' => 11200,
            'delivery_fee' => 1200,
            'payment_method' => $paymentMethod,
        ]);
    }

    private function payment(Order $order): PaymentTransaction
    {
        return PaymentTransaction::where('order_id', $order->id)->sole();
    }

    public function test_new_cash_order_opens_pending_payment_and_is_unpaid(): void
    {
        $order = $this->createOrder();

        $this->assertSame(PaymentStatus::UNPAID, $order->fresh()->payment_status);
        $payment = $this->payment($order);
        $this->assertSame('cash', $payment->provider);
        $this->assertSame(PaymentTransaction::TYPE_PAYMENT, $payment->type);
        $this->assertSame(PaymentTransaction::STATUS_PENDING, $payment->status);
        $this->assertSame(11200, $payment->amount); // minor units, same as orders.total
        $this->assertSame('TMT', $payment->currency);
    }

    public function test_order_placed_through_api_gets_a_payment_transaction(): void
    {
        $user = User::factory()->create(['phone' => '+99361234567']);
        $product = Product::factory()->create(['vendor_id' => $this->restaurant->id, 'price' => 100.00, 'is_available' => true]);
        $geo = Mockery::mock(GeoService::class)->makePartial();
        $geo->shouldReceive('checkDeliveryZone')->andReturn(new DeliveryZoneCheckResult('inside', true, 'Allowed'));
        $this->instance(GeoService::class, $geo);

        $response = $this->actingAs($user)->postJson('/api/v1/orders', [
            'vendor_id' => $this->restaurant->id,
            'items' => [['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => 1, 'unit_price' => 10000, 'total_price' => 10000]],
            'total' => 11200,
            'delivery_fee' => 1200,
            'customer_name' => 'Тест',
            'customer_phone' => '+99361234567',
            'address' => ['lat' => 39.0886, 'lon' => 63.5593, 'address' => 'Тестовая 1'],
            'payment_method' => 'cash',
        ]);

        $response->assertCreated();
        $order = Order::findOrFail($response->json('data.order_id'));
        $this->assertSame(11200, $this->payment($order)->amount);
        $this->assertSame(PaymentStatus::UNPAID, $order->payment_status);
    }

    public function test_delivery_collects_cash(): void
    {
        $order = $this->createOrder();

        $order->update(['status' => Order::STATUS_DELIVERED]);

        $this->assertSame(PaymentStatus::PAID, $order->fresh()->payment_status);
        $this->assertSame(PaymentTransaction::STATUS_SUCCEEDED, $this->payment($order)->status);
    }

    public function test_completed_pickup_collects_cash(): void
    {
        $order = $this->createOrder();

        $order->update(['status' => 'completed']);

        $this->assertSame(PaymentStatus::PAID, $order->fresh()->payment_status);
    }

    public function test_cancellation_closes_pending_payment(): void
    {
        $order = $this->createOrder();

        $order->update(['status' => Order::STATUS_CANCELLED]);

        $this->assertSame(PaymentStatus::UNPAID, $order->fresh()->payment_status);
        $this->assertSame(PaymentTransaction::STATUS_CANCELLED, $this->payment($order)->status);
    }

    public function test_repeated_completion_does_not_create_or_change_payments(): void
    {
        $order = $this->createOrder();
        $order->update(['status' => Order::STATUS_DELIVERED]);
        $order->update(['status' => 'completed']);

        $this->assertSame(1, PaymentTransaction::where('order_id', $order->id)->count());
        $this->assertSame(PaymentTransaction::STATUS_SUCCEEDED, $this->payment($order)->status);
    }

    public function test_order_created_before_payments_existed_is_paid_on_delivery(): void
    {
        // An order already in progress at deploy time: no payment transaction yet
        $order = Order::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'status' => Order::STATUS_DELIVERING,
            'payment_method' => 'cash',
            'total' => 11200,
        ]);
        PaymentTransaction::where('order_id', $order->id)->delete();

        $order->update(['status' => Order::STATUS_DELIVERED]);

        $this->assertSame(PaymentStatus::PAID, $order->fresh()->payment_status);
        $this->assertSame(PaymentTransaction::STATUS_SUCCEEDED, $this->payment($order)->status);
        $this->assertSame(11200, $this->payment($order)->amount);
    }

    public function test_method_without_gateway_does_not_break_order_creation(): void
    {
        $order = $this->createOrder('card');

        $this->assertSame(PaymentStatus::UNPAID, $order->fresh()->payment_status);
        $this->assertSame(0, PaymentTransaction::where('order_id', $order->id)->count());
    }

    public function test_panels_render_payment_status(): void
    {
        $order = $this->createOrder();
        $order->update(['status' => Order::STATUS_DELIVERED]);

        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ManageOrders::class)
            ->assertCanSeeTableRecords([$order])
            ->assertSee('Оплачен');

        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $this->restaurant->update(['owner_id' => $owner->id]);
        $this->actingAs($owner);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        Filament::setTenant($this->restaurant);
        Filament::bootCurrentPanel();
        Livewire::test(ListOrders::class)
            ->assertCanSeeTableRecords([$order])
            ->assertSee('Оплачен');
        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->assertFormSet(['payment_status' => PaymentStatus::PAID->value]);
    }
}
