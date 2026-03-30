<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Vendor\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function __construct(
        private readonly TenantContext $tenantContext
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $vendorId = $request->header('X-Vendor-ID');

        if (!$vendorId) {
            $host = $request->getHost();
            // Expected format: {vendor}.resto.local
            if (preg_match('/^([^.]+)\.resto\.local$/', $host, $matches)) {
                $vendorId = $matches[1];
            }
        }

        if ($vendorId) {
            $this->tenantContext->setCurrentVendor($vendorId);
        }

        return $next($request);
    }
}
