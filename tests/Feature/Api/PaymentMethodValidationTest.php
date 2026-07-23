<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domains\Geo\Services\DeliveryZoneCheckResult;
use App\Domains\Geo\Services\GeoService;
use App\Domains\Menu\Models\Product;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodValidationTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;
    private Product $product;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::factory()->create(['is_active' => true]);
        $this->restaurant->update(['vendor_id' => $this->restaurant->id]);

        $this->product = Product::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'price' => 100.00,
            'is_available' => true,
        ]);

        $this->user = User::factory()->create(['phone' => '+99361234567']);

        $this->mock(GeoService::class, function ($mock) {
            $mock->shouldReceive('checkDeliveryZone')
                ->andReturn(new DeliveryZoneCheckResult('inside', true, 'Allowed'));
            $mock->shouldReceive('calculateDeliveryFee')->andReturn(0);
        });
    }

    private function payloadWithPayment(string $paymentMethod): array
    {
        return [
            'vendor_id' => $this->restaurant->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'quantity' => 1,
                    'unit_price' => 10000,
                    'total_price' => 10000,
                    'modifiers' => [],
                ],
            ],
            'total' => 10000,
            'delivery_fee' => 0,
            'customer_name' => 'Тест',
            'customer_phone' => '+99361234567',
            'address' => [
                'lat' => 39.0886,
                'lon' => 63.5593,
                'address' => 'Тестовая 1',
            ],
            'payment_method' => $paymentMethod,
        ];
    }

    public function test_cash_payment_accepted(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->payloadWithPayment('cash'));

        $response->assertStatus(201)->assertJson(['success' => true]);
    }

    public function test_terminal_payment_accepted(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->payloadWithPayment('terminal'));

        $response->assertStatus(201)->assertJson(['success' => true]);
    }

    public function test_online_payment_accepted(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->payloadWithPayment('online'));

        $response->assertStatus(201)->assertJson(['success' => true]);
    }

    public function test_old_card_value_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->payloadWithPayment('card'));

        $response->assertStatus(422)
            ->assertJsonPath('reason', 'invalid_payment_method');
    }

    public function test_old_sbp_value_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->payloadWithPayment('sbp'));

        $response->assertStatus(422)
            ->assertJsonPath('reason', 'invalid_payment_method');
    }

    public function test_garbage_value_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $this->payloadWithPayment('bitcoin'));

        $response->assertStatus(422)
            ->assertJsonPath('reason', 'invalid_payment_method');
    }

    public function test_null_payment_method_defaults_to_cash(): void
    {
        $payload = $this->payloadWithPayment('cash');
        unset($payload['payment_method']);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $payload);

        // Should pass validation since null triggers default 'cash'
        $response->assertStatus(201)->assertJson(['success' => true]);
    }
}
