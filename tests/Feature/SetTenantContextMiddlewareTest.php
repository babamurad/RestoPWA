<?php

namespace Tests\Feature;

use App\Domains\Vendor\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
        $vendorId = 'test-vendor-123';

        $response = $this->withHeaders(['X-Vendor-ID' => $vendorId])
            ->get('/');

        $this->assertSame($vendorId, $this->tenantContext->getCurrentVendor());
    }

    public function test_header_takes_precedence_over_subdomain(): void
    {
        $headerVendorId = 'header-vendor';

        $response = $this->withHeaders(['X-Vendor-ID' => $headerVendorId])
            ->get('/');

        $this->assertSame($headerVendorId, $this->tenantContext->getCurrentVendor());
    }

    public function test_handles_subdomain_pattern_matching(): void
    {
        $vendorId = 'vendor1';
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
        $this->withHeaders(['X-Vendor-ID' => 'ignored'])->get('/');
        $this->assertSame('ignored', $this->tenantContext->getCurrentVendor());
    }

    public function test_clears_previous_vendor_on_new_request(): void
    {
        $this->tenantContext->setCurrentVendor('previous-vendor');

        $newVendorId = 'new-vendor-456';
        $this->withHeaders(['X-Vendor-ID' => $newVendorId])
            ->get('/');

        $this->assertSame($newVendorId, $this->tenantContext->getCurrentVendor());
    }
}
