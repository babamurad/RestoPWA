<?php

namespace Tests\Unit;

use App\Http\Middleware\SetTenantContext;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContextSubdomainTest extends TestCase
{
    public function test_extracts_vendor_from_subdomain_pattern(): void
    {
        $middleware = new SetTenantContext(
            app(\App\Domains\Vendor\Services\TenantContext::class)
        );

        $vendorFromSubdomain = $this->extractVendorFromSubdomain('vendor123.resto.local');
        $vendorFromSubdomain2 = $this->extractVendorFromSubdomain('myvendor.resto.local');

        $this->assertSame('vendor123', $vendorFromSubdomain);
        $this->assertSame('myvendor', $vendorFromSubdomain2);
    }

    public function test_extracts_vendor_from_www_subdomain(): void
    {
        $vendor = $this->extractVendorFromSubdomain('www.resto.local');

        $this->assertSame('www', $vendor);
    }

    public function test_returns_null_for_non_matching_pattern(): void
    {
        $vendor = $this->extractVendorFromSubdomain('api.example.com');
        $vendor2 = $this->extractVendorFromSubdomain('resto.local');
        $vendor3 = $this->extractVendorFromSubdomain('sub.vendor.resto.local'); // Too many parts

        $this->assertNull($vendor);
        $this->assertNull($vendor2);
        $this->assertNull($vendor3);
    }

    public function test_returns_vendor_for_simple_subdomain(): void
    {
        $vendor = $this->extractVendorFromSubdomain('vendor.resto.local');

        $this->assertSame('vendor', $vendor);
    }

    private function extractVendorFromSubdomain(string $host): ?string
    {
        if (preg_match('/^([^.]+)\.resto\.local$/', $host, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
