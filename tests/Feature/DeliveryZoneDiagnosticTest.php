<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Geo\Services\GeoService;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\OrderRejectReason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryZoneDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->restaurant = Restaurant::factory()->create([
            'is_active' => true,
            'delivery_zones' => null, // No zone configured
        ]);
        $this->restaurant->update(['vendor_id' => $this->restaurant->id]);
        $this->user = User::factory()->create();
    }

    public function test_diagnostic_returns_zone_missing_when_unconfigured(): void
    {
        $geoService = app(GeoService::class);
        $result = $geoService->checkDeliveryZone(55.0, 37.0, $this->restaurant->id);

        $this->assertEquals('zone_missing', $result->status);
        $this->assertFalse($result->isAllowed());
        $this->assertEquals('Зона доставки ресторана не настроена.', $result->messageForUser());
    }

    public function test_precondition_validator_rejects_with_zone_not_configured(): void
    {
        $payload = [
            'vendor_id' => $this->restaurant->id,
            'items' => [
                [
                    'product_id' => 'some-product',
                    'product_name' => 'Product Name',
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'total_price' => 1000,
                ],
            ],
            'total' => 1500,
            'delivery_fee' => 500,
            'address' => [
                'lat' => 55.0,
                'lon' => 37.0,
                'address' => 'Street name',
                'name' => 'User Name',
                'phone' => '+99312345678',
            ],
            'payment_method' => 'card',
        ];

        $validator = app(\App\Domains\Order\Validators\OrderPreconditionValidator::class);
        $reason = $validator->validate($payload);

        $this->assertEquals(OrderRejectReason::ZONE_NOT_CONFIGURED, $reason);
    }

    public function test_postgis_zone_exists_accessor_is_not_gate(): void
    {
        $restaurantWithZone = Restaurant::factory()->create([
            'is_active' => true,
        ]);
        
        $geoJson = [
            'type' => 'MultiPolygon',
            'coordinates' => [
                [
                    [
                        [63.5, 39.0],
                        [63.6, 39.0],
                        [63.6, 39.1],
                        [63.5, 39.1],
                        [63.5, 39.0]
                    ]
                ]
            ]
        ];

        // Save delivery zones securely using updateDeliveryZone
        $restaurantWithZone->updateDeliveryZone($geoJson);

        // Verify that checkDeliveryZone evaluates directly and successfully (relying on SQLite fallback)
        $geoService = app(GeoService::class);
        $result = $geoService->checkDeliveryZone(39.05, 63.55, $restaurantWithZone->id);

        $this->assertEquals('inside', $result->status);
        $this->assertTrue($result->isAllowed());
    }
}
