<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Menu\Models\Product;
use App\Domains\Vendor\Models\Restaurant;
use App\Domains\Vendor\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductScopeTest extends TestCase
{
    use RefreshDatabase;

    private TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContext = app(TenantContext::class);
    }

    public function test_available_scope_returns_only_available_products(): void
    {
        $restaurant = Restaurant::factory()->create();
        $vendorId = $restaurant->id;

        Product::factory()->forVendor($vendorId)->create(['is_available' => true]);
        Product::factory()->forVendor($vendorId)->create(['is_available' => false]);
        Product::factory()->forVendor($vendorId)->create(['is_available' => true]);

        $this->tenantContext->setCurrentVendor($vendorId);

        $available = Product::available()->get();

        $this->assertCount(2, $available);
        $this->assertTrue($available->every(fn ($p) => $p->is_available === true));
    }

    public function test_available_scope_respects_vendor_scope(): void
    {
        $r1 = Restaurant::factory()->create();
        $r2 = Restaurant::factory()->create();

        Product::factory()->forVendor($r1->id)->create(['is_available' => true]);
        Product::factory()->forVendor($r2->id)->create(['is_available' => true]);

        $this->tenantContext->setCurrentVendor($r1->id);

        $products = Product::available()->get();

        $this->assertCount(1, $products);
        $this->assertSame($r1->id, $products->first()->vendor_id);
    }

    public function test_auto_sets_vendor_id_on_create(): void
    {
        $restaurant = Restaurant::factory()->create();
        $vendorId = $restaurant->id;
        $this->tenantContext->setCurrentVendor($vendorId);

        $product = Product::factory()->make(['vendor_id' => null]);
        $product->save();

        $this->assertSame($vendorId, $product->vendor_id);
    }

    public function test_money_cast_stores_as_cents(): void
    {
        $restaurant = Restaurant::factory()->create();
        $vendorId = $restaurant->id;
        $this->tenantContext->setCurrentVendor($vendorId);

        $product = Product::factory()->forVendor($vendorId)->create([
            'price' => 1999,
        ]);

        $this->assertSame(19.99, $product->price);
    }

    public function test_money_cast_accepts_float(): void
    {
        $restaurant = Restaurant::factory()->create();
        $vendorId = $restaurant->id;
        $this->tenantContext->setCurrentVendor($vendorId);

        $product = Product::factory()->make([
            'vendor_id' => $vendorId,
            'price' => 25.50,
        ]);
        $product->save();

        $product->refresh();

        $this->assertSame(25.5, $product->price);
    }
}
