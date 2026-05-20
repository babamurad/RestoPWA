<?php

namespace Tests\Feature;

use App\Domains\Menu\Models\Product;
use App\Domains\Vendor\Models\Restaurant;
use App\Domains\Vendor\Services\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
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

    public function test_returns_only_vendor_products(): void
    {
        $r1 = Restaurant::factory()->create();
        $r2 = Restaurant::factory()->create();

        $p1 = Product::factory()->forVendor($r1->id)->create();
        $p2 = Product::factory()->forVendor($r1->id)->create();
        Product::factory()->forVendor($r2->id)->create();

        $this->tenantContext->setCurrentVendor($r1->id);

        $products = Product::all();

        $this->assertCount(2, $products);
        $this->assertTrue($products->contains('id', $p1->id));
        $this->assertTrue($products->contains('id', $p2->id));
    }

    public function test_returns_all_products_without_scope(): void
    {
        $r1 = Restaurant::factory()->create();
        $r2 = Restaurant::factory()->create();

        Product::factory()->forVendor($r1->id)->create();
        Product::factory()->forVendor($r2->id)->create();

        $this->tenantContext->setCurrentVendor($r1->id);

        $products = Product::withoutGlobalScopes()->get();

        $this->assertCount(2, $products);
    }

    public function test_returns_nothing_when_no_vendor_context(): void
    {
        $r1 = Restaurant::factory()->create();
        $r2 = Restaurant::factory()->create();

        Product::factory()->forVendor($r1->id)->create();
        Product::factory()->forVendor($r2->id)->create();

        $this->tenantContext->setCurrentVendor(null);

        $products = Product::all();

        $this->assertCount(2, $products);
    }

    public function test_auto_sets_vendor_id_on_create(): void
    {
        $r = Restaurant::factory()->create();
        $this->tenantContext->setCurrentVendor($r->id);

        $product = Product::factory()->make(['vendor_id' => null]);
        $product->save();

        $this->assertSame($r->id, $product->vendor_id);
    }

    public function test_does_not_override_existing_vendor_id(): void
    {
        $r1 = Restaurant::factory()->create();
        $r2 = Restaurant::factory()->create();
        $this->tenantContext->setCurrentVendor($r1->id);

        $product = Product::factory()->make(['vendor_id' => $r2->id]);
        $product->save();

        $this->assertSame($r2->id, $product->vendor_id);
    }

    public function test_does_not_auto_set_vendor_when_no_context(): void
    {
        $this->tenantContext->setCurrentVendor(null);

        $product = Product::factory()->make(['vendor_id' => null]);

        $this->expectException(QueryException::class);
        $product->save();
    }

    public function test_find_or_fail_respects_scope(): void
    {
        $r1 = Restaurant::factory()->create();
        $r2 = Restaurant::factory()->create();

        $p1 = Product::factory()->forVendor($r1->id)->create();
        Product::factory()->forVendor($r2->id)->create();

        $this->tenantContext->setCurrentVendor($r1->id);

        $found = Product::findOrFail($p1->id);

        $this->assertSame($p1->id, $found->id);
    }

    public function test_find_or_fail_throws_when_not_found(): void
    {
        $r1 = Restaurant::factory()->create();
        $r2 = Restaurant::factory()->create();

        Product::factory()->forVendor($r1->id)->create();
        $p2 = Product::factory()->forVendor($r2->id)->create();

        $this->tenantContext->setCurrentVendor($r1->id);

        $this->expectException(ModelNotFoundException::class);

        Product::findOrFail($p2->id);
    }

    public function test_local_scopes_work_with_global_scope(): void
    {
        $r1 = Restaurant::factory()->create();
        $r2 = Restaurant::factory()->create();

        Product::factory()->forVendor($r1->id)->create(['is_available' => true]);
        Product::factory()->forVendor($r1->id)->create(['is_available' => false]);
        Product::factory()->forVendor($r2->id)->create(['is_available' => true]);

        $this->tenantContext->setCurrentVendor($r1->id);

        $activeProducts = Product::available()->get();

        $this->assertCount(1, $activeProducts);
        $this->assertSame($r1->id, $activeProducts->first()->vendor_id);
        $this->assertTrue($activeProducts->first()->is_available);
    }
}
