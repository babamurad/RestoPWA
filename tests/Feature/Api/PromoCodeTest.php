<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domains\Geo\Services\DeliveryZoneCheckResult;
use App\Domains\Geo\Services\GeoService;
use App\Domains\Menu\Models\Product;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Services\OrderService;
use App\Domains\Promo\Models\PromoCode;
use App\Domains\Promo\Models\PromoCodeUsage;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\OrderCancelReason;
use App\Enums\UserRole;
use App\Filament\Resources\PromoCodeResource\Pages\CreatePromoCode;
use App\Filament\Resources\PromoCodeResource\Pages\ListPromoCodes;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

/**
 * Promo codes: the discount comes off the food only, is calculated on the server,
 * and a use of the code is taken (and given back on cancellation) with the order.
 */
class PromoCodeTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    private Product $product;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::factory()->create(['is_active' => true, 'delivery_fee' => 12.00, 'min_order' => 0, 'commission_percent' => 10]);
        $this->product = Product::factory()->create(['vendor_id' => $this->restaurant->id, 'price' => 100.00, 'is_available' => true]);
        $this->user = User::factory()->create(['phone' => '+99361234567']);

        $geo = Mockery::mock(GeoService::class)->makePartial();
        $geo->shouldReceive('checkDeliveryZone')->andReturn(new DeliveryZoneCheckResult('inside', true, 'Allowed'));
        $this->instance(GeoService::class, $geo);
    }

    private function code(array $attributes = []): PromoCode
    {
        return PromoCode::create($attributes + ['code' => 'PLOV20', 'percent' => 20]);
    }

    private function cart(int $quantity = 1): array
    {
        return [['product_id' => $this->product->id, 'quantity' => $quantity]];
    }

    private function apply(string $code, int $quantity = 1, ?User $as = null)
    {
        $request = $as ? $this->actingAs($as) : $this;

        return $request->postJson('/api/v1/cart/apply-promo', [
            'code' => $code, 'vendor_id' => $this->restaurant->id, 'items' => $this->cart($quantity),
        ]);
    }

    private function order(array $overrides = [], ?User $as = null)
    {
        $quantity = $overrides['quantity'] ?? 1;
        unset($overrides['quantity']);

        return $this->actingAs($as ?? $this->user)->postJson('/api/v1/orders', array_merge([
            'vendor_id' => $this->restaurant->id,
            'delivery_type' => 'pickup',
            'items' => [[
                'product_id' => $this->product->id, 'product_name' => 'x', 'quantity' => $quantity,
                'unit_price' => 10000, 'total_price' => 10000 * $quantity, 'modifiers' => [],
            ]],
            'total' => 10000 * $quantity,
            'delivery_fee' => 0,
            'customer_name' => 'Тест',
            'customer_phone' => '+99361234567',
            'address' => ['lat' => 39.0886, 'lon' => 63.5593, 'address' => 'Самовывоз'],
            'payment_method' => 'cash',
        ], $overrides));
    }

    public function test_percent_code_is_case_insensitive_and_priced_from_the_database(): void
    {
        $this->code();

        $this->apply(' plov20 ', 2)->assertOk()
            ->assertJsonPath('data.code', 'PLOV20')
            ->assertJsonPath('data.subtotal', 20000)
            ->assertJsonPath('data.discount', 4000);
    }

    public function test_percent_discount_is_capped_and_fixed_discount_never_exceeds_the_food(): void
    {
        $this->code(['code' => 'CAP', 'percent' => 50, 'max_discount' => 15]);
        $this->code(['code' => 'BIG', 'percent' => null, 'amount' => 500]);

        $this->apply('CAP')->assertJsonPath('data.discount', 1500);
        $this->apply('BIG')->assertJsonPath('data.discount', 10000);
    }

    public function test_rejections(): void
    {
        $this->code(['code' => 'OFF', 'is_active' => false]);
        $this->code(['code' => 'OLD', 'expires_at' => now()->subDay()]);
        $this->code(['code' => 'SOON', 'starts_at' => now()->addDay()]);
        $this->code(['code' => 'MIN', 'min_order' => 500]);
        $this->code(['code' => 'OTHER', 'restaurant_id' => Restaurant::factory()->create()->id]);

        foreach (['NOPE' => 'not_found', 'OFF' => 'inactive', 'OLD' => 'expired', 'SOON' => 'not_started', 'MIN' => 'min_order', 'OTHER' => 'wrong_restaurant'] as $code => $reason) {
            $this->apply($code)->assertStatus(422)->assertJsonPath('reason', $reason);
        }
    }

    public function test_order_carries_the_discount_and_takes_a_use(): void
    {
        $promo = $this->code();

        // The discount the client claims is ignored: 20% of 100 = 20, not 99
        $this->order(['promo_code' => 'plov20', 'discount_amount' => 9900, 'total' => 8000])->assertCreated();

        $order = Order::sole();
        $this->assertEquals(80.00, $order->total);
        $this->assertEquals(20.00, $order->discount_amount);
        $this->assertSame('PLOV20', $order->promo_code);
        $this->assertSame($promo->id, $order->promo_code_id);
        $this->assertSame(1, $promo->fresh()->used_count);
        $this->assertSame(1, PromoCodeUsage::where('order_id', $order->id)->count());
        $this->assertEquals(80.00, $order->foodTotal());
    }

    public function test_total_is_always_recalculated_with_the_discount(): void
    {
        $this->code();

        // The client forgot the discount: the server's total (80) is the one that is stored
        $this->order(['promo_code' => 'PLOV20', 'total' => 10000])->assertCreated();

        $this->assertEquals(80.00, Order::sole()->total);
    }

    public function test_invalid_code_rejects_the_order_with_a_reason(): void
    {
        $this->order(['promo_code' => 'NOPE'])
            ->assertStatus(422)
            ->assertJsonPath('reason', 'invalid_promo')
            ->assertJsonPath('details.promo_reason', 'not_found');
        $this->assertSame(0, Order::count());
    }

    public function test_per_user_and_total_limits(): void
    {
        $promo = $this->code(['usage_limit' => 2]);
        $other = User::factory()->create(['phone' => '+99361234568']);
        $third = User::factory()->create(['phone' => '+99361234569']);

        $this->order(['promo_code' => 'PLOV20'], $this->user)->assertCreated();

        // The same user cannot use it again
        $this->apply('PLOV20', 1, $this->user)->assertStatus(422)->assertJsonPath('reason', 'user_limit');

        $this->order(['promo_code' => 'PLOV20'], $other)->assertCreated();

        // Two uses are gone: nobody else gets it, neither in the cart nor at checkout
        $this->apply('PLOV20', 1, $third)->assertStatus(422)->assertJsonPath('reason', 'usage_limit');
        $this->order(['promo_code' => 'PLOV20'], $third)->assertStatus(422)->assertJsonPath('details.promo_reason', 'usage_limit');

        $this->assertSame(2, $promo->fresh()->used_count);
    }

    public function test_cancelled_order_gives_the_use_back(): void
    {
        $promo = $this->code(['usage_limit' => 1]);
        $this->order(['promo_code' => 'PLOV20', 'total' => 8000])->assertCreated();

        app(OrderService::class)->cancel(Order::sole(), Order::CANCELLED_BY_CUSTOMER, OrderCancelReason::CHANGED_MIND);

        $this->assertSame(0, $promo->fresh()->used_count);
        $this->assertSame(0, PromoCodeUsage::count());
        $this->apply('PLOV20', 1, $this->user)->assertOk();
    }

    public function test_commission_is_taken_from_the_discounted_food(): void
    {
        $this->code();
        $this->order(['promo_code' => 'PLOV20', 'total' => 8000])->assertCreated();

        $order = Order::sole();
        $order->update(['status' => Order::STATUS_COMPLETED]);

        // 10% of 80 (food after the discount), not of 100
        $this->assertEquals(8.00, $order->fresh()->commission_amount);
    }

    public function test_admin_manages_promo_codes(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CreatePromoCode::class)
            ->fillForm(['code' => 'summer15', 'percent' => 15, 'per_user_limit' => 1, 'min_order' => 20, 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $promo = PromoCode::where('code', 'SUMMER15')->sole();
        $this->assertSame(15.0, $promo->percent);
        $this->assertEquals(20.0, $promo->min_order);

        // Either a percent or an amount, not both and not neither
        Livewire::test(CreatePromoCode::class)
            ->fillForm(['code' => 'BAD', 'percent' => 10, 'amount' => 5, 'per_user_limit' => 1])
            ->call('create')
            ->assertHasFormErrors(['percent', 'amount']);

        Livewire::test(ListPromoCodes::class)
            ->assertCanSeeTableRecords([$promo])
            ->assertSee('15%');
    }
}
