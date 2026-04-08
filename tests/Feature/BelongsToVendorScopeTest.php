<?php

namespace Tests\Feature;

use App\Domains\Vendor\Models\Restaurant;
use App\Domains\Vendor\Services\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BelongsToVendorScopeTest extends TestCase
{
    use RefreshDatabase;

    private TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContext = app(TenantContext::class);
    }

    public function test_returns_only_vendor_restaurants(): void
    {
        $vendor1 = 'vendor-1';
        $vendor2 = 'vendor-2';

        $r1 = Restaurant::factory()->forVendor($vendor1)->create();
        $r2 = Restaurant::factory()->forVendor($vendor1)->create();
        Restaurant::factory()->forVendor($vendor2)->create();

        $this->tenantContext->setCurrentVendor($vendor1);

        $restaurants = Restaurant::all();

        $this->assertCount(2, $restaurants);
        $this->assertTrue($restaurants->contains('id', $r1->id));
        $this->assertTrue($restaurants->contains('id', $r2->id));
    }

    public function test_returns_all_restaurants_without_scope(): void
    {
        $vendor1 = 'vendor-1';
        $vendor2 = 'vendor-2';

        Restaurant::factory()->forVendor($vendor1)->create();
        Restaurant::factory()->forVendor($vendor2)->create();

        $this->tenantContext->setCurrentVendor($vendor1);

        $restaurants = Restaurant::withoutGlobalScopes()->get();

        $this->assertCount(2, $restaurants);
    }

    public function test_returns_nothing_when_no_vendor_context(): void
    {
        Restaurant::factory()->forVendor('vendor-1')->create();
        Restaurant::factory()->forVendor('vendor-2')->create();

        $this->tenantContext->setCurrentVendor(null);

        $restaurants = Restaurant::all();

        $this->assertCount(0, $restaurants);
    }

    public function test_auto_sets_vendor_id_on_create(): void
    {
        $vendorId = 'auto-vendor-123';
        $this->tenantContext->setCurrentVendor($vendorId);

        $restaurant = Restaurant::factory()->make(['vendor_id' => null]);

        $restaurant->save();

        $this->assertSame($vendorId, $restaurant->vendor_id);
    }

    public function test_does_not_override_existing_vendor_id(): void
    {
        $currentVendor = 'current-vendor';
        $existingVendor = 'existing-vendor';
        $this->tenantContext->setCurrentVendor($currentVendor);

        $restaurant = Restaurant::factory()->make(['vendor_id' => $existingVendor]);
        $restaurant->save();

        $this->assertSame($existingVendor, $restaurant->vendor_id);
    }

    public function test_does_not_auto_set_vendor_when_no_context(): void
    {
        $this->tenantContext->setCurrentVendor(null);

        $restaurant = Restaurant::factory()->make(['vendor_id' => null]);
        $restaurant->save();

        $this->assertNull($restaurant->vendor_id);
    }

    public function test_find_or_fail_respects_scope(): void
    {
        $vendor1 = 'vendor-1';
        $vendor2 = 'vendor-2';

        $r1 = Restaurant::factory()->forVendor($vendor1)->create();
        Restaurant::factory()->forVendor($vendor2)->create();

        $this->tenantContext->setCurrentVendor($vendor1);

        $found = Restaurant::findOrFail($r1->id);

        $this->assertSame($r1->id, $found->id);
    }

    public function test_find_or_fail_throws_when_not_found(): void
    {
        $vendor1 = 'vendor-1';
        $vendor2 = 'vendor-2';

        Restaurant::factory()->forVendor($vendor1)->create();
        $r2 = Restaurant::factory()->forVendor($vendor2)->create();

        $this->tenantContext->setCurrentVendor($vendor1);

        $this->expectException(ModelNotFoundException::class);

        Restaurant::findOrFail($r2->id);
    }

    public function test_local_scopes_work_with_global_scope(): void
    {
        $vendor1 = 'vendor-1';
        $vendor2 = 'vendor-2';

        Restaurant::factory()->forVendor($vendor1)->create(['is_active' => true]);
        Restaurant::factory()->forVendor($vendor1)->create(['is_active' => false]);
        Restaurant::factory()->forVendor($vendor2)->create(['is_active' => true]);

        $this->tenantContext->setCurrentVendor($vendor1);

        $activeRestaurants = Restaurant::active()->get();

        $this->assertCount(1, $activeRestaurants);
        $this->assertSame($vendor1, $activeRestaurants->first()->vendor_id);
        $this->assertTrue($activeRestaurants->first()->is_active);
    }
}
