<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domains\Vendor\Models\Restaurant;
use App\Domains\Vendor\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyVendorIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_binding_resolves_by_restaurant_id_not_legacy_vendor_id(): void
    {
        $restaurant = Restaurant::factory()->create(['vendor_id' => fake()->uuid()]);

        app(TenantContext::class)->setCurrentVendor($restaurant->id);

        $this->assertSame($restaurant->id, app('tenant')->id);
        $this->assertInstanceOf(Restaurant::class, app('tenant'));
    }

    public function test_diagnostic_command_runs(): void
    {
        Restaurant::factory()->create(['vendor_id' => fake()->uuid()]);

        $this->artisan('restaurants:check-vendor-id')->assertSuccessful();
    }
}
