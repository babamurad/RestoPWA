<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Geo\Models\District;
use App\Domains\Menu\Models\Product;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Domains\Vendor\Models\RestaurantDeliveryPrice;
use App\Enums\UserRole;
use App\Filament\Resources\DistrictResource\Pages\CreateDistrict;
use App\Filament\Vendor\Resources\RestaurantResource\Pages\EditRestaurant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * City districts are shared; each restaurant prices the districts it delivers to.
 * Uses the real GeoService (no mocks): district lookup is done in PHP.
 */
class DistrictDeliveryPricingTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    private Product $product;

    private User $customer;

    private District $center;

    private District $north;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::factory()->create(['is_active' => true, 'delivery_fee' => 50.00, 'min_order' => 0]);
        $this->restaurant->update(['vendor_id' => $this->restaurant->id]);
        $this->product = Product::factory()->create(['vendor_id' => $this->restaurant->id, 'price' => 100.00, 'is_available' => true]);
        $this->customer = User::factory()->create(['phone' => '+99361234567']);

        // Two neighbouring squares (lat 37.90–38.00 and 38.00–38.10, lon 58.30–58.40)
        $this->center = District::create(['name' => 'Центр', 'boundary' => $this->square(37.90, 38.00)]);
        $this->north = District::create(['name' => 'Север', 'boundary' => $this->square(38.00, 38.10)]);
    }

    private function square(float $latFrom, float $latTo, float $lonFrom = 58.30, float $lonTo = 58.40): array
    {
        return ['type' => 'Polygon', 'coordinates' => [[
            [$lonFrom, $latFrom], [$lonTo, $latFrom], [$lonTo, $latTo], [$lonFrom, $latTo], [$lonFrom, $latFrom],
        ]]];
    }

    private function price(District $district, float $price): void
    {
        RestaurantDeliveryPrice::create(['restaurant_id' => $this->restaurant->id, 'district_id' => $district->id, 'price' => $price]);
    }

    private function placeOrder(float $lat, float $lon): TestResponse
    {
        return $this->actingAs($this->customer)->postJson('/api/v1/orders', [
            'vendor_id' => $this->restaurant->id,
            'items' => [[
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'quantity' => 1,
                'unit_price' => 10000,
                'total_price' => 10000,
            ]],
            'total' => 10000,
            'delivery_fee' => 0,
            'customer_name' => 'Тест',
            'customer_phone' => '+99361234567',
            'address' => ['lat' => $lat, 'lon' => $lon, 'address' => 'Тестовая 1'],
            'payment_method' => 'cash',
        ]);
    }

    public function test_district_contains_points_inside_its_boundary_except_holes(): void
    {
        $withHole = District::create(['name' => 'С дыркой', 'boundary' => json_encode(['type' => 'Polygon', 'coordinates' => [
            [[0, 0], [10, 0], [10, 10], [0, 10], [0, 0]],
            [[4, 4], [6, 4], [6, 6], [4, 6], [4, 4]],
        ]])]);

        $this->assertTrue($this->center->contains(37.95, 58.35));
        $this->assertFalse($this->center->contains(38.05, 58.35));
        $this->assertTrue($withHole->contains(2, 2));
        $this->assertFalse($withHole->contains(5, 5)); // inside the hole
        $this->assertSame('MultiPolygon', $withHole->boundary['type']);
    }

    public function test_order_is_charged_the_restaurant_price_for_its_district(): void
    {
        $this->price($this->center, 10.00);
        $this->price($this->north, 18.00);

        $response = $this->placeOrder(38.05, 58.35);

        $response->assertCreated();
        $order = Order::findOrFail($response->json('data.order_id'));
        $this->assertEquals(18.00, $order->delivery_fee); // not the flat 50
        $this->assertEquals(118.00, $order->total);
    }

    public function test_district_without_restaurant_price_is_outside_delivery(): void
    {
        $this->price($this->center, 10.00);

        $this->placeOrder(38.05, 58.35) // Север: not priced
            ->assertJsonPath('reason', 'outside_delivery_zone');

        $this->assertSame(0, Order::count());
    }

    public function test_point_outside_every_district_is_rejected(): void
    {
        $this->price($this->center, 10.00);

        $this->placeOrder(40.00, 60.00)->assertJsonPath('reason', 'outside_delivery_zone');
    }

    public function test_inactive_district_is_not_delivered_to(): void
    {
        $this->price($this->center, 10.00);
        $this->price($this->north, 18.00);
        $this->north->update(['is_active' => false]);

        $this->placeOrder(38.05, 58.35)->assertJsonPath('reason', 'outside_delivery_zone');
    }

    public function test_zone_check_returns_district_and_price(): void
    {
        $this->price($this->center, 10.00);

        $this->postJson('/api/v1/geo/zone-check', ['vendor_id' => $this->restaurant->id, 'lat' => 37.95, 'lon' => 58.35])
            ->assertOk()
            ->assertJsonPath('data.in_zone', true)
            ->assertJsonPath('data.district', 'Центр')
            ->assertJsonPath('data.delivery_fee', 10);

        $this->postJson('/api/v1/geo/zone-check', ['vendor_id' => $this->restaurant->id, 'lat' => 38.05, 'lon' => 58.35])
            ->assertOk()
            ->assertJsonPath('data.in_zone', false);
    }

    public function test_restaurant_and_cart_show_price_from_cheapest_district(): void
    {
        $this->price($this->center, 10.00);
        $this->price($this->north, 18.00);

        $restaurants = collect($this->getJson('/api/v1/restaurants')->json())->keyBy('id');
        $this->assertEquals(10.0, $restaurants[$this->restaurant->id]['delivery_fee_from']);

        $this->actingAs($this->customer)->postJson('/api/v1/cart/sync', [
            'vendor_id' => $this->restaurant->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'price' => 100.00]],
        ])
            ->assertOk()
            ->assertJsonPath('data.delivery_fee', 10)
            ->assertJsonPath('data.delivery_fee_is_estimate', true);
    }

    public function test_restaurant_without_district_prices_keeps_flat_fee(): void
    {
        $restaurants = collect($this->getJson('/api/v1/restaurants')->json())->keyBy('id');
        $this->assertNull($restaurants[$this->restaurant->id]['delivery_fee_from']);

        $this->actingAs($this->customer)->postJson('/api/v1/cart/sync', [
            'vendor_id' => $this->restaurant->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'price' => 100.00]],
        ])
            ->assertJsonPath('data.delivery_fee', 50)
            ->assertJsonPath('data.delivery_fee_is_estimate', false);
    }

    public function test_admin_creates_district_and_restaurateur_prices_it(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CreateDistrict::class)
            ->fillForm([
                'name' => 'Юг',
                'city' => 'Ашхабад',
                'boundary' => json_encode(['type' => 'MultiPolygon', 'coordinates' => [$this->square(37.80, 37.90)['coordinates']]]),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $south = District::where('name', 'Юг')->firstOrFail();
        $this->assertTrue($south->contains(37.85, 58.35));

        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $this->restaurant->update(['owner_id' => $owner->id]);
        $this->actingAs($owner);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        Filament::setTenant($this->restaurant);
        Filament::bootCurrentPanel();

        Livewire::test(EditRestaurant::class, ['record' => $this->restaurant->getRouteKey()])
            ->set('data.deliveryPrices', [
                'a' => ['district_id' => $south->id, 'price' => 12],
                'b' => ['district_id' => $this->center->id, 'price' => 8],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $prices = RestaurantDeliveryPrice::where('restaurant_id', $this->restaurant->id)->get()->keyBy('district_id');
        $this->assertCount(2, $prices);
        $this->assertEquals(12.0, $prices[$south->id]->price);
        $this->assertEquals(8.0, $prices[$this->center->id]->price);
    }
}
