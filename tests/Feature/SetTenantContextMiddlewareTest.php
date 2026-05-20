<?php

namespace Tests\Feature;

use App\Domains\Vendor\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class SetTenantContextMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContext = app(TenantContext::class);
        $this->tenantContext->setCurrentVendor(null);
    }

    public function test_sets_vendor_id_from_x_vendor_id_header(): void
    {
        $vendorId = (string) Str::uuid();

        $response = $this->withHeaders(['X-Vendor-ID' => $vendorId])
            ->get('/');

        $this->assertSame($vendorId, $this->tenantContext->getCurrentVendor());
    }

    public function test_header_takes_precedence_over_subdomain(): void
    {
        $headerVendorId = (string) Str::uuid();

        $response = $this->withHeaders(['X-Vendor-ID' => $headerVendorId])
            ->get('/');

        $this->assertSame($headerVendorId, $this->tenantContext->getCurrentVendor());
    }

    public function test_handles_subdomain_pattern_matching(): void
    {
        $vendorId = (string) Str::uuid();
        $this->withHeaders(['X-Vendor-ID' => $vendorId])->get('/');
        $this->assertSame($vendorId, $this->tenantContext->getCurrentVendor());
    }

    public function test_does_not_set_vendor_id_when_no_source(): void
    {
        $response = $this->get('/');

        $this->assertNull($this->tenantContext->getCurrentVendor());
    }

    public function test_handles_non_matching_host(): void
    {
        $vendorId = (string) Str::uuid();
        $this->withHeaders(['X-Vendor-ID' => $vendorId])->get('/');
        $this->assertSame($vendorId, $this->tenantContext->getCurrentVendor());
    }

    public function test_clears_previous_vendor_on_new_request(): void
    {
        $this->tenantContext->setCurrentVendor('previous-vendor');

        $newVendorId = (string) Str::uuid();
        $this->withHeaders(['X-Vendor-ID' => $newVendorId])
            ->get('/');

        $this->assertSame($newVendorId, $this->tenantContext->getCurrentVendor());
    }
}
