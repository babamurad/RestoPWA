<?php

namespace Tests\Unit;

use App\Domains\Vendor\Services\TenantContext;
use PHPUnit\Framework\TestCase;

class TenantContextTest extends TestCase
{
    public function test_initial_state_is_null(): void
    {
        $context = new TenantContext;

        $this->assertNull($context->getCurrentVendor());
    }

    public function test_can_set_and_get_vendor_id(): void
    {
        $context = new TenantContext;
        $vendorId = 'vendor-123';

        $context->setCurrentVendor($vendorId);

        $this->assertSame($vendorId, $context->getCurrentVendor());
    }

    public function test_can_set_null_vendor_id(): void
    {
        $context = new TenantContext;
        $context->setCurrentVendor('vendor-123');

        $context->setCurrentVendor(null);

        $this->assertNull($context->getCurrentVendor());
    }

    public function test_can_overwrite_vendor_id(): void
    {
        $context = new TenantContext;

        $context->setCurrentVendor('vendor-1');
        $context->setCurrentVendor('vendor-2');

        $this->assertSame('vendor-2', $context->getCurrentVendor());
    }
}
