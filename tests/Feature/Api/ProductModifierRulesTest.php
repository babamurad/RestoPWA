<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domains\Geo\Services\DeliveryZoneCheckResult;
use App\Domains\Geo\Services\GeoService;
use App\Domains\Menu\Models\Product;
use App\Domains\Menu\Services\ProductModifiers;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class ProductModifierRulesTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    private Product $pizza;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::factory()->create(['is_active' => true, 'min_order' => 0]);

        $this->pizza = Product::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'price' => 100.00,
            'is_available' => true,
            'modifiers' => [
                ['name' => 'Размер', 'type' => 'radio', 'required' => true, 'options' => [
                    ['name' => 'Маленькая', 'price' => 0, 'is_default' => true],
                    ['name' => 'Большая', 'price' => 50],
                ]],
                ['name' => 'Топпинг', 'type' => 'checkbox', 'max' => 2, 'options' => [
                    ['name' => 'Сыр', 'price' => 30],
                    ['name' => 'Грибы', 'price' => 25],
                    ['name' => 'Бекон', 'price' => 40],
                ]],
            ],
        ]);

        $this->user = User::factory()->create(['phone' => '+99361234567']);

        $this->instance(GeoService::class, Mockery::mock(GeoService::class, function ($mock) {
            $mock->shouldReceive('checkDeliveryZone')->andReturn(new DeliveryZoneCheckResult('inside', true, 'Allowed'));
            $mock->shouldReceive('calculateDeliveryFee')->andReturn(0.0);
        }));
    }

    private function option(string $group, string $option): string
    {
        foreach (ProductModifiers::groups($this->pizza->modifiers) as $g) {
            foreach ($g['options'] as $o) {
                if ($g['name'] === $group && $o['name'] === $option) {
                    return $o['id'];
                }
            }
        }
        $this->fail("No option {$group}/{$option}");
    }

    private function order(array $modifiers): TestResponse
    {
        return $this->actingAs($this->user)->postJson('/api/v1/orders', [
            'vendor_id' => $this->restaurant->id,
            'items' => [['product_id' => $this->pizza->id, 'quantity' => 2, 'modifiers' => $modifiers]],
            'customer_name' => 'Test User',
            'customer_phone' => '+99312345678',
            'address' => ['lat' => 1.0, 'lon' => 1.0, 'address' => 'Test Street 1'],
            'payment_method' => 'cash',
        ]);
    }

    public function test_menu_shows_groups_with_ids_and_rules(): void
    {
        $response = $this->getJson('/api/v1/menu/product/'.$this->pizza->id)->assertOk();

        $groups = $response->json('modifiers');
        $this->assertSame(['Размер', 'Топпинг'], array_column($groups, 'name'));
        $this->assertSame([true, false], array_column($groups, 'required'));
        $this->assertSame([1, 2], array_column($groups, 'max'));
        $this->assertNotEmpty($groups[0]['id']);
        $this->assertNotEmpty($groups[0]['options'][1]['id']);
        $this->assertEquals(50, $groups[0]['options'][1]['price']);
    }

    public function test_a_valid_choice_is_priced_from_the_options_and_stored_with_names(): void
    {
        $response = $this->order([
            ['option_id' => $this->option('Размер', 'Большая')],
            ['option_id' => $this->option('Топпинг', 'Сыр'), 'price' => 0],
        ])->assertStatus(201);

        $order = Order::findOrFail($response->json('data.order_id'));

        // (100 + 50 + 30) × 2
        $this->assertSame(360.0, $order->total);
        $this->assertSame(18000, $order->items[0]['unit_price']);
        $this->assertSame(['Большая', 'Сыр'], array_column($order->items[0]['modifiers'], 'name'));
        $this->assertSame(['Размер', 'Топпинг'], array_column($order->items[0]['modifiers'], 'group'));
    }

    public function test_a_required_group_left_empty_is_refused(): void
    {
        $this->order([['option_id' => $this->option('Топпинг', 'Сыр')]])
            ->assertStatus(422)
            ->assertJsonPath('reason', 'invalid_modifiers');

        $this->order([])->assertStatus(422)->assertJsonPath('reason', 'invalid_modifiers');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_more_options_than_the_group_allows_is_refused(): void
    {
        $this->order([
            ['option_id' => $this->option('Размер', 'Маленькая')],
            ['option_id' => $this->option('Топпинг', 'Сыр')],
            ['option_id' => $this->option('Топпинг', 'Грибы')],
            ['option_id' => $this->option('Топпинг', 'Бекон')],
        ])->assertStatus(422)->assertJsonPath('reason', 'invalid_modifiers');

        $this->order([
            ['option_id' => $this->option('Размер', 'Маленькая')],
            ['option_id' => $this->option('Размер', 'Большая')],
        ])->assertStatus(422)->assertJsonPath('reason', 'invalid_modifiers');
    }

    public function test_a_dish_without_modifiers_is_not_affected(): void
    {
        $this->pizza->update(['modifiers' => []]);

        $this->order([])->assertStatus(201);
    }

    public function test_cart_sync_prices_the_choice_and_echoes_the_line_key(): void
    {
        $response = $this->postJson('/api/v1/cart/sync', [
            'vendor_id' => $this->restaurant->id,
            'items' => [
                ['key' => 'line-a', 'product_id' => $this->pizza->id, 'quantity' => 2, 'price' => 15000, 'modifiers' => [
                    ['option_id' => $this->option('Размер', 'Большая')],
                ]],
                ['key' => 'line-b', 'product_id' => $this->pizza->id, 'quantity' => 1, 'price' => 10000, 'modifiers' => [
                    ['option_id' => $this->option('Размер', 'Маленькая')],
                    ['option_id' => $this->option('Топпинг', 'Бекон')],
                ]],
            ],
        ])->assertOk();

        $items = collect($response->json('data.validated_items'))->keyBy('key');
        $this->assertSame(15000, $items['line-a']['price']);
        // 100 + 0 + 40 = 140, the client thought 100
        $this->assertSame(14000, $items['line-b']['price']);
        $this->assertSame(1, count($response->json('data.price_changes')));
        $this->assertSame(440.0, (float) $response->json('data.subtotal'));
    }

    public function test_cart_sync_drops_a_line_whose_choice_no_longer_fits(): void
    {
        $response = $this->postJson('/api/v1/cart/sync', [
            'vendor_id' => $this->restaurant->id,
            'items' => [['key' => 'old-line', 'product_id' => $this->pizza->id, 'quantity' => 1, 'modifiers' => []]],
        ])->assertOk();

        $this->assertSame([], $response->json('data.validated_items'));
        $this->assertSame('old-line', $response->json('data.unavailable_items.0.key'));
    }
}
